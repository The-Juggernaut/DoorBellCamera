<!DOCTYPE html>
<html lang="en">
<title>House cam</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<style>
  body,
  h1,
  h2,
  h3,
  h4,
  h5 {
    font-family: "Poppins", sans-serif
  }

  body {
    font-size: 16px;
  }

  .w3-half img {
    margin-bottom: -6px;
    margin-top: 16px;
    opacity: 0.8;
    cursor: pointer
  }

  .w3-half img:hover {
    opacity: 1
  }
</style>

<body>

  <!-- Sidebar/menu -->
  <nav class="w3-sidebar w3-red w3-collapse w3-top w3-large w3-padding" style="z-index:3;width:300px;font-weight:bold;"
    id="mySidebar"><br>
    <a href="javascript:void(0)" onclick="w3_close()" class="w3-button w3-hide-large w3-display-topleft"
      style="width:100%;font-size:22px">Close Menu</a>
    <div class="w3-container">
      <h3 class="w3-padding-64"><b>House<br>Cam</b></h3>
    </div>
    <div class="w3-bar-block">
      <a href="/" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white">Home</a>
      <a href="/live.php" onclick="w3_close()" class="w3-bar-item  w3-white w3-button w3-hover-white">Live</a>

      <a href="/motion.php" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white">Motion</a>
      <a href="/config.php" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white">Settings</a>
    </div>
  </nav>

  <!-- Top menu on small screens -->
  <header class="w3-container w3-top w3-hide-large w3-red w3-xlarge w3-padding">
    <a href="javascript:void(0)" class="w3-button w3-red w3-margin-right" onclick="w3_open()">☰</a>
    <span>House Cam</span>
  </header>

  <!-- Overlay effect when opening sidebar on small screens -->
  <div class="w3-overlay w3-hide-large" onclick="w3_close()" style="cursor:pointer" title="close side menu"
    id="myOverlay"></div>

  <!-- !PAGE CONTENT! -->
  <div class="w3-main" style="margin-left:340px;margin-right:40px">

    <!-- Header -->
    <div class="w3-container" style="margin-top:80px" id="showcase">
      <h1 class="w3-jumbo"><b>Live view</b></h1>
      <h2 id="imageArea" class="w3-xxxlarge w3-text-red"></h2>
      <hr style="width:50px;border:5px solid red" class="w3-round">
    </div>

    <!-- Photo grid (modal) -->
    <div class="w3-row-padding">

      <img id="video" width="100%"></img>
      <p>Load full version? <input type="checkbox" id="fullres"></p>
      <button class="w3-button w3-blue" onclick="loadVideo()">Load</button>

    </div>


    <!-- End page content -->
  </div>

  <!-- W3.CSS Container -->
  <div class="w3-light-grey w3-container w3-padding-32" style="margin-top:75px;padding-right:58px">
    <p class="w3-right">Powered by <a href="https://www.w3schools.com/w3css/default.asp" title="W3.CSS" target="_blank"
        class="w3-hover-opacity">w3.css</a></p>
  </div>

  <script>
    // Script to open and close sidebar
    function w3_open() {
      document.getElementById("mySidebar").style.display = "block";
      document.getElementById("myOverlay").style.display = "block";
    }

    function w3_close() {
      document.getElementById("mySidebar").style.display = "none";
      document.getElementById("myOverlay").style.display = "none";
    }

    // Modal Image Gallery
    function onClick(element) {
      document.getElementById("img01").src = element.src;
      document.getElementById("modal01").style.display = "block";
      var captionText = document.getElementById("caption");
      captionText.innerHTML = element.alt;
    }
  </script>


  <script>
    //On page load, decide if the full stream should be selected
    var cip = "<?php echo $_SERVER['REMOTE_ADDR']; ?>"
    if (cip.includes("192.168.1")) {
      console.log("IP is lan, default is full stream")
      document.getElementById("fullres").checked = true;
    }

    //Get the camera name
    var camName = "";
    $.getJSON('http://<?php echo $_SERVER['HTTP_HOST'];?>:8000/config', function (config) {
      camName = config.Name;
    });

    //LoadVideo button click
    var socket = "";
    var imgErr = document.getElementById("imageArea");
    var imgBox = document.getElementById('video');
    var askClose = false;
    function loadVideo() {
      //Close existing connection
      try {
        askClose = true;
        socket.close();
      } catch (err) {
        askClose = false;
        console.log("I tried to close the socket but got this " + err.message);
      }


      //Reset image err
      imgErr.innerHTML = ""
      //Set socket
      if (document.getElementById("fullres").checked) {
        socket = new WebSocket("ws://<?php echo $_SERVER['HTTP_HOST'];?>:8000/stream/" + encodeURI(camName))
      } else {
        socket = new WebSocket("ws://<?php echo $_SERVER['HTTP_HOST'];?>:8000/mobilestream/" + encodeURI(camName))
      }

      //Connect to socket
      var update = function () {

        // Log errors
        socket.onclose = function (error) {
          if (!askClose) {
            imgErr.innerHTML = "Socket has been closed. Connection to camera has failed"
            imgBox.src = "";
          }
          askClose = false;
        };

        socket.onmessage = function (event) {
          if (event.data == "PING") {
            socket.send("PONG")
          } else {
            decoded = atob(event.data)
            imgBox.src = "data:image/jpg;base64, " + event.data
          }
        }
      };
      window.setTimeout(update);
      //Activate alerts
      alerts();

    }

    var aSocket = "";

    function alerts() {
      //Close existing connection
      try {
        aSocket.close();
      } catch (err) {
        console.log("I tried to close the alert socket but got this " + err.message);
      }
      aSocket = new WebSocket("ws://<?php echo $_SERVER['HTTP_HOST'];?>:8000/motionAlert")
      var update = function () {
        // Log errors
        aSocket.onclose = function (error) {
          console.log("Alert closed")
        };

        aSocket.onmessage = function (event) {
          if (event.data == "PING") {
            socket.send("PONG")
          } else {
            obj = JSON.parse(event.data)
            date = new Date(obj.Time * 1000)
            // Hours part from the timestamp
            var hours = date.getHours();
            // Minutes part from the timestamp
            var minutes = "0" + date.getMinutes();
            // Seconds part from the timestamp
            var seconds = "0" + date.getSeconds();
            imgErr.innerHTML = "Alert for " + obj.Name + " At " + hours + ":" + minutes + ":" + seconds;
            console.log("Alert " + event.data)
            //long.innerHTML = "<img src='data:image/jpg;base64, "+event.data+"' alt='image'>"
          }

        }
      };
      window.setTimeout(update);
    }

  </script>


</body>

</html>