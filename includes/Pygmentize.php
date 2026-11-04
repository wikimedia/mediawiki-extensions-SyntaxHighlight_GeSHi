<?php
/**
 * Copyright (C) 2021 Kunal Mehta <legoktm@debian.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace MediaWiki\SyntaxHighlight;

use MediaWiki\Config\Config;
use MediaWiki\Shell\CommandFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Shellbox\Command\BoxedCommand;
use Shellbox\ShellboxError;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\WANObjectCache;
use Wikimedia\Stats\StatsFactory;

/**
 * Wrapper around the `pygmentize` command
 */
class Pygmentize {

	public function __construct(
		private readonly Config $config,
		private readonly BagOStuff $srvCache,
		private readonly WANObjectCache $wanCache,
		private readonly CommandFactory $commandFactory,
		private readonly StatsFactory $statsFactory,
	) {
	}

	/**
	 * If no pygmentize is configured, use bundled
	 */
	public function useBundled(): bool {
		return $this->config->get( 'PygmentizePath' ) === false;
	}

	/**
	 * Get a real path to pygmentize
	 *
	 * @internal Only public for updateLexerList.php
	 */
	public function getPath(): string {
		// If $wgPygmentizePath is unset, use the bundled copy.
		return $this->config->get( 'PygmentizePath' ) ?: __DIR__ . '/../pygments/pygmentize';
	}

	/**
	 * Get the version of pygments (cached)
	 *
	 * WARNING: This is called a lot in the critical path
	 *
	 * An error here will fatal page views, edits, and load.php requests (T292663).
	 *
	 * This value controls cache invalidation and propagation (by virtue of being
	 * part of other cache keys in this class). In order for a Pygments upgrade to
	 * take effect and to avoid runtime warnings from mixing different versions
	 * (i.e. validating against old lexer list from getLexers), this version must
	 * be bumped shortly after every upgrade, but not before the upgrade (as that
	 * would cache old data under new keys without self-correcting).
	 */
	public function getVersion(): string {
		// Optimization: Process-cache because this is called N times during a request
		static $version;
		if ( $version === null ) {
			if ( $this->useBundled() ) {
				$version = self::getBundledVersion();
			} else {
				$version = $this->srvCache->getWithSetCallback(
					$this->srvCache->makeGlobalKey( 'pygmentize-version' ),
					// Optimization: Spread by 60 seconds to reduce stampedes
					BagOStuff::TTL_HOUR + mt_rand( 0, 60 ),
					function () {
						return $this->wanCache->buildGetWithSetCallback()
							->globalKey( 'pygmentize-version' )
							// Should be less than 1h to avoid upper layer renewing stale data
							->lifetime( 30 * WANObjectCache::TTL_MINUTE )
							// Avoid fatal errors by renewing the last known version
							// if Shellbox responds HTTP 503 (T292663)
							->allowStale( WANObjectCache::TTL_WEEK )
							// Avoid fatal errors by falling back to a placeholder (T292663)
							//
							// The placeholder must differ from any real version to avoid
							// cache corruption.
							//
							// The placeholder should be close to the real version, because
							// there are some low-stakes compare_version calls with it.
							->busyValue( self::getBundledVersion() . '-samebutdifferent' )
							->callback( $this->fetchVersion( ... ) )
							->fetch();
					}
				);
			}
		}

		return $version;
	}

	/**
	 * Get the version of bundled pygments
	 */
	private static function getBundledVersion(): string {
		return trim( file_get_contents( __DIR__ . '/../pygments/VERSION' ) );
	}

	/**
	 * Shell out to get installed pygments version
	 *
	 * @internal For use by WANObjectCache/BagOStuff only
	 * @throws PygmentsException
	 */
	public function fetchVersion(): string {
		$result = $this->boxedCommand()
			->params( $this->getPath(), '-V' )
			->includeStderr()
			->execute();
		$this->recordShellout( 'version' );

		$output = $result->getStdout();
		if ( $result->getExitCode() != 0 ||
			!preg_match( '/^Pygments version (\S+),/', $output, $matches )
		) {
			throw new PygmentsException( $output );
		}

		return $matches[1];
	}

	/**
	 * Get the list of supported lexers by pygments
	 *
	 * @return array<string,true>
	 */
	public function getLexers(): array {
		// Optimization: Always return lexers from bundled version (T292663)
		//
		// If using $wgPygmentizePath with Shellbox (like WMF), this will be
		// briefly out of sync until the bundled version and Shellbox are both updated.
		//
		// Impact:
		// - New lexer, Shellbox updated first:
		//   Fallback to no highlighting, no warning because expected and statically rejected.
		// - New lexer, bundled updated first:
		//   Fallback to no highlighting, with runtime warning.
		// - Lexer removed, Shellbox updated first:
		//   Fallback to no highlighting, with runtime warning.
		// - Lexer removed, bundled updated first:
		//   Fallback to no highlighting, no warning because expected and statically rejected.
		return require __DIR__ . '/../SyntaxHighlight.lexers.php';
	}

	/**
	 * Actually highlight some text
	 *
	 * @param string $lexer Lexer name
	 * @param string $code Code to highlight
	 * @param array $options Options to pass to pygments
	 * @return string
	 * @throws PygmentsException
	 */
	public function highlight( $lexer, $code, array $options ): string {
		$optionPairs = [];
		foreach ( $options as $k => $v ) {
			$optionPairs[] = "{$k}={$v}";
		}
		$this->recordShellout( 'highlight' );

		try {
			$result = $this->boxedCommand()
				->params(
					$this->getPath(),
					'-l', $lexer,
					'-f', 'html',
					'-O', implode( ',', $optionPairs ),
					'file'
				)
				->inputFileFromString( 'file', $code )
				->execute();
		} catch ( ShellboxError $exception ) {
			// If we have trouble sending or receiving over the network to
			// Shellbox, we technically don't know if the command succeed or failed,
			// but, treat the highlight() command as recoverable by wrapping this in
			// PygmentsException. This permits the Parser tag to fallback to
			// plainCodeWrap(), thus avoiding a fatal on pageviews (T292663).
			throw new PygmentsException( 'ShellboxError', 0, $exception );
		} catch ( ClientExceptionInterface $exception ) {
			throw new PygmentsException( 'Http client error', 0, $exception );
		}

		$output = $result->getStdout();
		if ( $result->getExitCode() != 0 ) {
			if ( $output === "" || $output === null ) {
				// Stdout was empty, report stderr instead
				$output = $result->getStderr();
			}
			throw new PygmentsException( (string)$output );
		}

		return $output;
	}

	/**
	 * @internal Only public for updateLexerList.php
	 */
	public function boxedCommand(): BoxedCommand {
		$command = $this->commandFactory
			->createBoxed( 'syntaxhighlight' )
			->disableNetwork()
			->firejailDefaultSeccomp()
			->routeName( 'syntaxhighlight-pygments' );

		if ( wfIsWindows() ) {
			// Python requires the SystemRoot environment variable to initialize (T300223)
			$command->environment( [
				'SystemRoot' => getenv( 'SystemRoot' ),
			] );
		}

		return $command;
	}

	/**
	 * Track how often we do each type of shellout in statsd
	 *
	 * @param string $type Type of shellout
	 */
	private function recordShellout( $type ) {
		$this->statsFactory
			->getCounter( 'syntaxhighlight_shell_total' )
			->setLabel( 'type', $type )
			->increment();
	}
}
