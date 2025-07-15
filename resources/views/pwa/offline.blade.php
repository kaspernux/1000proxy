<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - {{ config("app.name") }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .offline-container {
            text-align: center;
            padding: 2rem;
            max-width: 500px;
        }
        
        .offline-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .offline-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .offline-message {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .offline-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .offline-button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .offline-button:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }
        
        .retry-button {
            background: rgba(76, 175, 80, 0.3);
            border-color: rgba(76, 175, 80, 0.5);
        }
        
        .retry-button:hover {
            background: rgba(76, 175, 80, 0.5);
        }
        
        @media (max-width: 480px) {
            .offline-title {
                font-size: 2rem;
            }
            
            .offline-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .offline-button {
                min-width: 200px;
            }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.8; }
            50% { opacity: 1; }
            100% { opacity: 0.8; }
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon pulse">📡</div>
        <h1 class="offline-title">You're Offline</h1>
        <p class="offline-message">
            It looks like you're not connected to the internet. Some features may not be available, 
            but you can still browse previously loaded content.
        </p>
        <div class="offline-actions">
            <button onclick="window.location.reload()" class="offline-button retry-button">
                Try Again
            </button>
            <a href="/" class="offline-button">
                Go Home
            </a>
        </div>
    </div>
    
    <script>
        // Auto-retry when back online
        window.addEventListener("online", () => {
            window.location.reload();
        });
        
        // Check connection status
        if (navigator.onLine) {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    </script>
</body>
</html>