<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Error | LearnTogether</title>
  <link rel="stylesheet" href="/LearnTogether/CSS/error.css">
</head>
<body>

  <div class="wrapper">
    <div class="error-box">
      <img src="images/drama.png" alt="404 Illustration" class="error-img">

      <h1 class="error-code">404</h1>
      <h2 class="error-message">Page Not Found</h2>
      <p class="error-description">
        The page you're looking for doesn't exist or may have been moved.
      </p>

      <button onclick="goBack()" class="home-btn">Go Back</button>

    </div>
  </div>

  <script>
    function goBack() {
      if (document.referrer) {
        window.location.href = document.referrer;
      } else {
        window.location.href = '/LearnTogether/landingpage.php';
      }
    }
  </script>

</body>
</html>
