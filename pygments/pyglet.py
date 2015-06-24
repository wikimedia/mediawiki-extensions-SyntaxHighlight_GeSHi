# -*- coding: utf-8 -*-
"""
  Pyglet -- a Pygments micro-service

  This is a small web service that listens on 127.0.0.1:31337.
  It provides two endpoints:

    /highlight
      Accepts POST requests with multipart/form-data with fields
      'code' (for source code to highlight) and 'lexer' (for
      lexer name). Additional form fields are passed to the HTML
      formatter.

    /lexers
      Returns a JSON list of available lexers.

  The port Pyglet will listen on is configurable via the --listen-port
  parameter.

  In addition to Pygments itself, Pyglet requires gevent.


  Copyright 2015 Ori Livneh <ori@wikimedia.org>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

"""
import sys
reload(sys)
sys.setdefaultencoding('utf-8')

import argparse
import syslog

import flask
import gevent.wsgi
import pygments
import pygments.formatters
import pygments.lexers


syslog.openlog(logoption=syslog.LOG_PID, facility=syslog.LOG_USER)

ap = argparse.ArgumentParser(description='a syntax-highlighting web service')
ap.add_argument('--listen-port', type=int, default=31337)
args = ap.parse_args()
addr = ('127.0.0.1', args.listen_port)

app = flask.Flask(__name__)
formatter = pygments.formatters.HtmlFormatter(encoding='utf-8')

lexer_names = set()
for name, aliases, _, _ in pygments.lexers.get_all_lexers():
    lexer_names.add(name.lower())
    lexer_names.update(alias.lower() for alias in aliases)


@app.route('/highlight', methods=['POST'])
def highlight_code():
    options = {k: v for k, v in flask.request.form.items()}

    try:
        code = options.pop('code')
        lexer_name = options.pop('lexer').lower()
        assert lexer_name in lexer_names
    except (AssertionError, KeyError):
        flask.abort(400)  # Bad request

    if len(code) > 102400:
        flask.abort(413)  # Request Entity Too Large

    try:
        lexer = pygments.lexers.get_lexer_by_name(lexer_name)
        formatter = pygments.formatters.HtmlFormatter(**options)
        return pygments.highlight(code, lexer, formatter)
    except:
        flask.abort(500)


@app.route('/lexers')
def list_lexers():
    return flask.jsonify(data=lexer_names)


syslog.syslog('Listening on http://%s:%s/...' % addr)
http_server = gevent.wsgi.WSGIServer(addr, app)
http_server.serve_forever()
