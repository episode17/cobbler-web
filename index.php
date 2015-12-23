<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, maximum-scale=1">
    <title>Cobbler</title>
    <link rel="shortcut icon" href="favicon.png">
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
            background:#000 url(img/bg.png);
            color:#fff;
            font-size: 16px;
        }
        
        .content {
            position: relative;
            top: 50%;
            -webkit-transform: translateY(-50%);
            -ms-transform: translateY(-50%);
            transform: translateY(-50%);
        }
        
        .top {
            overflow: hidden;
        }
        
        .logo {
            margin-left: 20px;
            margin-bottom: 20px;
            display: block;
        }
        
        .counter {
            position: absolute;
            bottom: 28px;
            right: 20px;
            font-weight:bold;
            font-size: 52px;
        }
        
        .counter:after {
            content:'1,000,000,000,000';
            position: absolute;
            bottom:0;
            right:0;
            font-weight:bold;
            font-size: 52px;
            color:#151515;
            z-index:-1;
        }
        
        .progress-bar {
            background-color:#1c1c1c;
            height:16px;
            clear: both;
            overflow:hidden;
        }
        
        .progress-bar__inner {
            background-color:#e9ce20;
            height:100%;
            width:100%;
            transform: translateX(0);
            margin-left: -100%;
        }
        
        .prograss-label {
            font-weight: bold;
            font-size: 26px;
            text-align: left;
            visibility:hidden;
            padding:0 20px;
        }
        
        .extra {
            position:fixed;
            bottom:0;
            right:0;
        }
        
        .ttc,
        .speed {
            
            font-size:14px;
            background-color:#e9ce20;
            color:#000;
            padding:2px 4px;
            float:left;
            margin-left:10px;
        }
        
        .ttc i { 
            /*font-weight:normal;*/
            font-style:normal;
        }
        
        .tether-element { visibility:visible; }
        .tether-element.tether-pinned-bottom { display:none; }
        
        @media screen and (max-width: 500px) {
            .counter {
                position:static;
                margin: 0 0 20px 20px;
            }
        }
        
        @media screen and (max-width: 700px) {
            .counter { font-size:30px; }
            .counter:after { display:none; }
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="top">
            <img class="logo" src="img/logo.png" width="174" height="89">
            <div class="counter js-counter">0</div>
        </div>
        <div class="progress-bar">
            <div class="progress-bar__inner js-progress-bar"></div>
        </div>
    </div>
    <div class="extra">
        <div class="ttc js-ttc">Est. TTC:</div>
        <div class="speed js-speed">0<b>/s</b></div>
    </div>
    <div class="prograss-label js-progress-label">0.00%</div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <script src="libs/tether/js/tether.min.js"></script>
    <script src="http://episode17.com:9090/socket.io/socket.io.js"></script>
    <script>
    
        var OBJECTIVE = 1000000000000; // A trillion, baby!
        
        // DOM
        var $bar;
        var $label;
        var $counter;
        var $speed;
        var $ttc;
        var tether;
        
        var socket;
        
        // Data
        var prevCompletion = 0;
        var count = 0;
        var speed = 0;
        
        // Render
        var interval;
        var now;
        var dt;
        var last;
        
        
        function update(newCount) {
            var newCompletion = Math.min(round10(newCount / OBJECTIVE * 100), 100);
            var countStr = groupThousands(newCount);
            
            $counter.text(countStr);
            document.title = countStr;
            
            updateExtra();
            
            if (newCompletion > prevCompletion) {
                $bar.css('transform', 'translateX(' + newCompletion + '%)');
                $label.text(newCompletion.toFixed(2) + '%');
                tether.position();
                
                prevCompletion = newCompletion;
            }
        }
        
        
        function intro() {            
            $({val: 0}).animate({val: count}, {
                step: function(now, fx) {
                    update(Math.round(now));
                },
                duration: 1000,
                easing: 'swing',
                complete: onIntroComplete
            });
        }
        
        
        function onIntroComplete() {            
            interval = setInterval(function(){
                now = timestamp();
                dt = (now - last) / 1000;
                render(dt);
                last = now;
            }, 1000 / 30);
        }
        
        
        function render(dt) {
            var newCount = count + Math.round(speed * dt);
            update(newCount);
            count = newCount;
        }
        
        
        function updateExtra() {
            $speed.html(Math.round(speed) + '<b>/s</b>');
            
            // var ttc = Math.round((OBJECTIVE - count) / speed / 60 / 60 /24);
            var ttc = Math.round((OBJECTIVE - count) / speed);
            $ttc.html('Est. TTC: <b>' + secsToTime(ttc) + '</b>');
        }

        
        // Start it
        $(function() {
            $bar = $('.js-progress-bar');
            $label = $('.js-progress-label');
            $counter = $('.js-counter');
            $speed = $('.js-speed');
            $ttc = $('.js-ttc');
            
            tether = new Tether({
                element: $label,
                target: $bar,
                attachment: 'top right',
                targetAttachment: 'bottom right',
                offset: '-10px -20px',
                constraints: [
                    {to: 'window', pin: true}
                ]
            });
            
            tether.position();
            
            // Sock it!
            socket = io('http://episode17.com:9090');
            
            socket.on('disconnect', function () {
                clearInterval(interval);
                prevCompletion = count = speed = 0;
            });
            
            socket.on('start', function (data) {                
                count = data.count;
                speed = data.speed;
                
                last = timestamp();
                intro()
            });
            
            socket.on('update', function (data) {                
                count = data.count;
                speed = data.speed;
            });
        });
        
        
        // Helpers
        function secsToTime(delta) {
            var days = Math.floor(delta / 86400);
            delta -= days * 86400;

            var hours = Math.floor(delta / 3600) % 24;
            delta -= hours * 3600;

            var minutes = Math.floor(delta / 60) % 60;
            delta -= minutes * 60;

            var seconds = delta % 60; 

            var result = days
                result += '<i>d</i> ' + (hours < 10 ? '0' + hours : hours);
                result += '<i>h</i> ' + (minutes < 10 ? '0' + minutes : minutes);
                result += '<i>m</i> ' + (seconds  < 10 ? '0' + seconds : seconds);
                result += '<i>s</i>';
                
            return result;
        }
        
        function groupThousands(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        
        function round10(val) {
            return Math.round(val * 100) / 100;
        }
        
        function timestamp() {
            return window.performance && window.performance.now ? window.performance.now() : new Date().getTime();
        }
        
    </script>
</body>
</html>