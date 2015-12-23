<?php
define('PANEL_WIDTH', 128);
define('PANEL_HEIGHT', 32);
define('CANVAS_SCALE', 4);

function getImageSrc($path) {
    $imageData = base64_encode(file_get_contents($path));
    return 'data:' . mime_content_type($path) . ';base64,' . $imageData;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Cobbler Panel</title>
    <style>
        * { margin:0; padding:0; list-style-type:none; }
        
        html {
            box-sizing: border-box;
            height:100%;
            overflow:hidden;
        }
        
        *, *:before, *:after {
            box-sizing: inherit;
        }
        
        body {
            height:100%;
            overflow:hidden;
            font-family:Arial;
            background:#333;
            color:#fff;
            font-size: 16px;
        }
        
        #panel {
            display:block;
            margin:0 auto;
            image-rendering: pixelated
        }
        
        #out {
            display:block;
            position:absolute;
            bottom:0;
            right:0;
        }
        
        #stats {
            position:absolute;
            bottom:0;
            left:0;
        }
    </style>
</head>
<body>
    <canvas id="panel" width="<?= PANEL_WIDTH; ?>" height="<?= PANEL_HEIGHT; ?>"></canvas>
    <img id="out" width="<?= PANEL_WIDTH; ?>" height="<?= PANEL_HEIGHT; ?>">
    <script src="stats.min.js"></script>
    <script src="http://episode17.com:9090/socket.io/socket.io.js"></script>
    <script>
        'use strict';
        
        // Stats
        var stats = new Stats();
        stats.setMode(0);
        
        document.body.appendChild(stats.domElement);
        
        // Font data 
        var glyphsData = [
            ['A', 5],
            ['B', 5],
            ['C', 5],
            ['D', 5],
            ['E', 5],
            ['F', 5],
            ['G', 5],
            ['H', 5],
            ['I', 3],
            ['J', 5],
            ['K', 5],
            ['L', 5],
            ['M', 5],
            ['N', 5],
            ['O', 5],
            ['P', 5],
            ['Q', 5],
            ['R', 5],
            ['S', 5],
            ['T', 5],
            ['U', 5],
            ['V', 5],
            ['W', 5],
            ['X', 5],
            ['Y', 5],
            ['Z', 5],
            ['a', 5],
            ['b', 5],
            ['c', 5],
            ['d', 5],
            ['e', 5],
            ['f', 4],
            ['g', 5],
            ['h', 5],
            ['i', 1],
            ['j', 5],
            ['k', 4],
            ['l', 2],
            ['m', 5],
            ['n', 5],
            ['o', 5],
            ['p', 5],
            ['q', 5],
            ['r', 5],
            ['s', 5],
            ['t', 3],
            ['u', 5],
            ['v', 5],
            ['w', 5],
            ['x', 5],
            ['y', 5],
            ['z', 5],
            ['0', 5],
            ['1', 5],
            ['2', 5],
            ['3', 5],
            ['4', 5],
            ['5', 5],
            ['6', 5],
            ['7', 5],
            ['8', 5],
            ['9', 5],
            ['$', 5],
            ['+', 5],
            ['-', 5],
            ['*', 4],
            ['/', 5],
            ['=', 5],
            ['%', 5],
            ['"', 3],
            ['\'', 1],
            ['#', 5],
            ['@', 6],
            ['&', 5],
            ['_', 5],
            ['(', 4],
            [')', 4],
            [',', 1],
            ['.', 1],
            [';', 1],
            [':', 1],
            ['?', 5],
            ['!', 1],
            ['\\', 5],
            ['|', 1],
            ['{', 4],
            ['}', 4],
            ['<', 4],
            ['>', 4],
            ['[', 3],
            [']', 3],
            ['^', 5],
            ['~', 6],
            ['©', 7]
        ];
        
        
        
        
        
        /**
         * Class helpers
         */
        Function.prototype.inherits = function(Parent) {
            function F() {}
            F.prototype = Parent.prototype;
            this.prototype = new F();
        };
        
        Function.prototype.extends = function(Parent) {
            this.inherits(Parent);
            this.prototype.constructor = this;
            this.prototype.parent = Parent.prototype;
            
            return this;
        };
        
        
        
        
        
        /**
         * Helpers
         */
        var Helpers = {
            
            secsToTime: function(delta) {
                var days = Math.floor(delta / 86400);
                delta -= days * 86400;

                var hours = Math.floor(delta / 3600) % 24;
                delta -= hours * 3600;

                var minutes = Math.floor(delta / 60) % 60;
                delta -= minutes * 60;

                var seconds = delta % 60; 

                var result = days
                    result += 'd ' + (hours < 10 ? '0' + hours : hours);
                    result += 'h ' + (minutes < 10 ? '0' + minutes : minutes);
                    result += 'm ' + (seconds  < 10 ? '0' + seconds : seconds);
                    result += 's';
                    
                return result;
            },
            
            groupThousands: function(x) {
                return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            },
            
            round10: function(val) {
                return Math.round(val * 100) / 100;
            }

        };
        
        
        
        
        
        /**
         * Font
         */
        function Font(src, height, data){
            this.sprite = new Image();
            this.sprite.src = src;
            
            this.height = height;
            this.glyphs = this._initGlyphs(data);
        }
        
        Font.prototype._initGlyphs = function(data) {
            var glyphs = {};
            var pos = 0;
            
            for (var i = 0, len = data.length; i < len; i++) {
                var w = data[i][1];
                glyphs[data[i][0]] = [pos, w];
                pos += w + 1;
            }
            
            return glyphs;
        }
        
        
        
        
        
        /**
         * Textline
         * TODO: Color, fonts, multiline?, static?
         * TODO: Get dimensions, move to constructor?
         */
        function Textline() {
            // TODO: Better dimensions interface
            this.height = 0;
            this.width = 0;
        }
        
        Textline.prototype.write = function(s, font, spacing) {
            var canvas = document.createElement('canvas'); 
            var ctx = canvas.getContext('2d');
            
            var pos = 0;
            for (var i = 0, len = s.length; i < len; i++) {
                if (s[i] == ' ') {
                    pos += spacing * 4;
                    continue;
                }
                
                var glyph = font.glyphs[s[i]];
                
                if (!glyph) continue;
                
                var w = glyph[1]
                
                ctx.drawImage(font.sprite, glyph[0], 0, w, font.height, pos, 0, w, font.height);
                pos += w + spacing;
            }
            
            this.height = font.height;
            // this.width = pos - spacing;
            this.width = pos;
            
            return canvas;
        }
        
        
        
        
        
        /**
         * Panel
         */
        function Panel(width, height, ctx) {
            this.ctx = ctx;
            this.width = width;
            this.height = height;
            this.elems = [];
        }
        
        Panel.prototype.render = function(dt) {
            // Reset
            this.ctx.fillStyle = '#000';
            this.ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            for (var i = 0, len = this.elems.length; i < len; i++) {
                this.elems[i].render(dt);
            }
        };
        
        
        
        
        
        /**
         * Panel Element
         */
        function PanelElement(ctx) {
            this.ctx = ctx;
            
            // Shorthands
            this.panelWidth = ctx.canvas.width;
            this.panelHeight = ctx.canvas.height;
        }
        
        PanelElement.prototype.render = function(dt) { }; 

        
        
        
        
        /**
         * Element: Counter
         */
        CounterElement.extends(PanelElement);

        function CounterElement(ctx, data) {
            this.parent.constructor.call(this, ctx);
            this.data = data;
            this.x = 0;
            this.y = 0;
        }
        
        CounterElement.prototype.render = function(dt) {
            var textline = new Textline();
            var count = ('0000000000000' + Math.round(this.data.count)).substr(-13, 13);
            
            this.ctx.drawImage(textline.write(Helpers.groupThousands(count), font_white, 1), 3, 4);
        };
        
        
        
        
        
        /**
         * Element: Markee
         * TODO: Figure out real infinite markee
         */
        MarkeeElement.extends(PanelElement);

        function MarkeeElement(ctx, data, objective) {
            this.parent.constructor.call(this, ctx);
            this.data = data;
            this.objective = objective;
            this.x = this.panelWidth;
            this.x2 = this.panelWidth + 260;
        }
        
        MarkeeElement.prototype.render = function(dt) {
            var pos = this.x;
            var textline = new Textline();
            
            this.ctx.drawImage(textline.write('SPEED: ', font_yellow, 2), pos, 17);
            this.ctx.drawImage(textline.write('SPEED: ', font_yellow, 2), pos + 1, 17);
            pos += textline.width;
            
            this.ctx.drawImage(textline.write(Math.round(this.data.speed) + '/s', font_white, 1), pos, 17);
            pos += textline.width;
            
            this.ctx.drawImage(textline.write('  TTC: ', font_yellow, 2), pos, 17);
            this.ctx.drawImage(textline.write('  TTC: ', font_yellow, 2), pos + 1, 17);
            pos += textline.width;
            
            var ttc = Math.round((this.objective - this.data.count) / this.data.speed);
            
            this.ctx.drawImage(textline.write(Helpers.secsToTime(ttc), font_white, 1), pos, 17);
            pos += textline.width;           
            
            this.x -= 1.0;
            
            if (this.x <= -pos + this.x) this.x = this.panelWidth + 160;
            
            // SECOND
            
            var pos2 = this.x2;
            var textline2 = new Textline();
            
            this.ctx.drawImage(textline2.write('SPEED: ', font_yellow, 2), pos2, 17);
            this.ctx.drawImage(textline2.write('SPEED: ', font_yellow, 2), pos2 + 1, 17);
            pos2 += textline2.width;
            
            this.ctx.drawImage(textline2.write(Math.round(this.data.speed) + '/s', font_white, 1), pos2, 17);
            pos2 += textline2.width;
            
            this.ctx.drawImage(textline2.write('  TTC: ', font_yellow, 2), pos2, 17);
            this.ctx.drawImage(textline2.write('  TTC: ', font_yellow, 2), pos2 + 1, 17);
            pos2 += textline2.width;
            
            this.ctx.drawImage(textline2.write(Helpers.secsToTime(ttc), font_white, 1), pos2, 17);
            pos2 += textline2.width;           
            
            
            this.x2 -= 1.0;
            
            if (this.x2 <= -pos2 + this.x2) this.x2 = this.panelWidth + 160;
        };
        
        
        
        
        
        /**
         * Element: Percentage bar
         */
        PercentageBarElement.extends(PanelElement);

        function PercentageBarElement(ctx, data, objective) {
            this.parent.constructor.call(this, ctx);
            this.data = data;
            this.objective = objective;
        }
        
        PercentageBarElement.prototype.render = function(dt) {
            var width = this.data.count / this.objective * this.panelWidth;
            var height = 3;
            
            ctx.fillStyle = '#ffcc33';
            ctx.fillRect(0, this.panelHeight - height, Math.floor(width), height);
        };
        
        
        
        
        
        /**
         * Element: Percentage label
         */
        PercentageLabelElement.extends(PanelElement);

        function PercentageLabelElement(ctx, data, objective) {
            this.parent.constructor.call(this, ctx);
            this.data = data;
            this.objective = objective;
        }
        
        PercentageLabelElement.prototype.render = function(dt) {
            var width = 32;
            var height = 11;
            ctx.fillStyle = '#ffcc33';
            ctx.fillRect(this.panelWidth - width, 2, width, height);
            
            var perc = Math.min(Helpers.round10(this.data.count / this.objective * 100), 100);
            
            var textline = new Textline();
            var line = textline.write(perc.toFixed(1) + '%', font_black, 2);

            // TODO: Fix bold hack
            this.ctx.drawImage(line, Math.round(this.panelWidth - (textline.width + 1 + width) / 2), 4);
            this.ctx.drawImage(line, Math.round(this.panelWidth - (textline.width + 1 + width) / 2) + 1, 4);
        };
        
        
        
        
        
        /**
         * Cobbler Panel
         */
        CobblerPanel.extends(Panel);
        
        function CobblerPanel(width, height, ctx, out) {
            this.parent.constructor.call(this, width, height, ctx);
            this.out = out;
            
            var self = this;
            
            // Data
            this.objective = 1000000000000;
            this.data = {
                count: 0,
                speed: 0
            }
            
            // Panel elements
            var counter = new CounterElement(this.ctx, this.data);
            var markee = new MarkeeElement(this.ctx, this.data, this.objective);
            var percentageBar = new PercentageBarElement(this.ctx, this.data, this.objective);
            var percentageLabel = new PercentageLabelElement(this.ctx, this.data, this.objective);
            
            this.elems.push(counter);
            this.elems.push(markee);
            this.elems.push(percentageBar);
            this.elems.push(percentageLabel);
            
            // Render loop
            this.fps = 40;
            this.interval = 1000 / this.fps;
            this.requestId;
            this.last;
            
            // Socket
            this.socket = io('http://episode17.com:9090');
            
            this.socket.on('disconnect', function () {
                self.stop();
                self.data.count = self.data.speed = 0;
            });
            
            this.socket.on('start', function (data) {
                self.data.count = data.count;
                self.data.speed = data.speed;
                
                self.start()
            });
            
            this.socket.on('update', function (data) {
                self.data.count = data.count;
                self.data.speed = data.speed;
            });
        }
        
        CobblerPanel.prototype.render = function(dt) {
            this.data.count += this.data.speed * dt;
            this.parent.render.call(this, dt);
            
            this.out.src = this.ctx.canvas.toDataURL('image/png');
        }
        
        CobblerPanel.prototype.start = function() {
            if (!this.requestId) {
                this.last = window.performance.now();
                this.frame();
            }
        }
        
        CobblerPanel.prototype.stop = function() {
            if (this.requestId) {
                cancelAnimationFrame(this.requestId);
                this.requestId = undefined;
            }
        }
        
        CobblerPanel.prototype.frame = function() {
            stats.begin();
            
            var self = this;
            var now = window.performance.now();
            var dt = now - this.last;
            
            if (dt > this.interval) {
                this.render(dt / 1000);
                this.last = now - (dt % this.interval);
                
                stats.end();
            }
            
            self.requestId = requestAnimationFrame(function() {
                self.frame();
            });
        }
        
        CobblerPanel.prototype.setFps = function(fps) {
            this.fps = fps;
            this.interval = 1000 / fps;
        }
        
        
        
        
        // APP ----------
        
        // TODO: Auto colors
        var font_white = new Font('<?= getImageSrc('font_1.png'); ?>', 8, glyphsData);
        var font_yellow = new Font('<?= getImageSrc('font_1_yellow.png'); ?>', 8, glyphsData);
        var font_black = new Font('<?= getImageSrc('font_1_black.png'); ?>', 8, glyphsData);
        
        var out = document.getElementById('out');
        var canvas = document.getElementById('panel');
        canvas.style.width = canvas.width * <?= CANVAS_SCALE; ?> + 'px';
        canvas.style.height = canvas.height * <?= CANVAS_SCALE; ?> + 'px';
        
        var ctx = canvas.getContext('2d');
        
        var cobblerPanel = new CobblerPanel(128, 32, ctx, out);
        // cobblerPanel.start();
    </script>
</body>
</html>