<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied | VehiScan RFID</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .error-code {
            font-size: 120px;
            font-weight: 800;
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 32px;
            color: #1a202c;
            margin-bottom: 16px;
        }

        p {
            font-size: 18px;
            color: #4a5568;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(250, 112, 154, 0.4);
        }

        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="icon">🔒</div>
        <div class="error-code">403</div>
        <h1>Access Denied</h1>
        <p>You don't have permission to access this resource. Please contact your administrator if you believe this is
            an error.</p>
        <a href="/" class="btn">Go to Homepage</a>
    </div>
</body>

</html>