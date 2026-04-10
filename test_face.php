<?php
session_start();
$_SESSION['user_id'] = 1; // ID d'un utilisateur existant
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Reconnaissance Faciale</title>
</head>
<body>
    <video id="video" autoplay playsinline style="width:400px;"></video>
    <canvas id="canvas" style="display:none;"></canvas>
    <button onclick="capture()">Capturer</button>
    <div id="result"></div>

    <script>
        let stream = null;
        
        async function startCamera() {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            document.getElementById('video').srcObject = stream;
        }
        
        async function capture() {
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            
            const imageData = canvas.toDataURL('image/jpeg');
            document.getElementById('result').innerHTML = 'Envoi...';
            
            const response = await fetch('index.php?page=register_face', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'face_image=' + encodeURIComponent(imageData)
            });
            
            const result = await response.json();
            document.getElementById('result').innerHTML = JSON.stringify(result);
        }
        
        startCamera();
    </script>
</body>
</html>
