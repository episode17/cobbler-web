<?php
define('PANEL_WIDTH', 128);
define('PANEL_HEIGHT', 32);
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
        
        #out {
            display:block;
            position:absolute;
            bottom:0;
            right:0;
        }
    </style>
</head>
<body>
    <img id="out" width="<?= PANEL_WIDTH; ?>" height="<?= PANEL_HEIGHT; ?>">
    <script src="http://episode17.com:9099/socket.io/socket.io.js"></script>
    <script>
        'use strict';
            
            var out = document.getElementById('out');
            var socket = io('http://episode17.com:9099');
            
            socket.on('disconnect', function () {
                    
            });
            
            socket.on('frame', function (data) {
                var frame = new Uint8ClampedArray(data)
            });
            
            socket.on('frame2', function (frame) {
                out.src = frame;
            });
    </script>
</body>
</html>