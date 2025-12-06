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
      <img src="../images/drama.png" alt="403 Illustration" class="error-img">

      <h1 class="error-code">403</h1>
      <h2 class="error-message">Access Forbidden</h2>
      <p class="error-description">
        You don't have permission to access this resource.
      </p>

      <button onclick="goBack()" class="home-btn">Go Back</button>

    </div>
  </div>

  <script>
    function goBack() {
      window.location.href = '/LearnTogether/login.php';
    }
  </script>

</body>
</html>
