<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dispatcher</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }
    body { background-color: #3b5870; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 20px; }
    .header { background-color: #2f4a5f; width: 100%; padding: 20px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
    .header h2 { color: #fff; font-size: 28px; }
    .nav-container { display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin: 20px 0; }
    .nav-button { background-color: #f4a825; padding: 10px 20px; border-radius: 8px; color: #fff; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; cursor: pointer; }
    .nav-button:hover { background-color: #ffcc00; transform: scale(1.05); }
    
    #map { height: 500px; width: 100%; max-width: 1000px; margin-top: 20px; border-radius: 10px; overflow: hidden; }
    .trace-input { margin-top: 20px; display: flex; gap: 10px; align-items: center; }
    .trace-input input { padding: 8px 12px; border-radius: 8px; border: none; width: 200px; }
    .trace-input button { padding: 10px 20px; background-color: #f4a825; border: none; border-radius: 8px; color: #fff; font-weight: bold; cursor: pointer; }
    .trace-input button:hover { background-color: #ffcc00; }
  </style>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
  <div class="header">
    <h2>World Map</h2>
    <div class="nav-container">
      <a class="nav-button" href="trucklocation.php">Trucking Location</a>
      <a class="nav-button" href="delieveries.php">Manage Deliveries</a>
      <a class="nav-button" href="notification.php"> Notification</a>
      <a class="nav-button" href="logout.php"> Logout</a>
    </div>
  </div>

  <div class="trace-input">
    <input type="number" id="trackerId" placeholder="Enter truck/location ID">
    <button onclick="traceLocation()">Trace</button>
  </div>

  <div id="map"></div>

  <script>
    // Initialize map
    var map = L.map('map').setView([0, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var marker;

    // Sample locations (truck IDs mapped to lat/lng)
    const locations = {
      101: { lat: 14.5995, lng: 120.9842, label: "Truck 101 - Manila" },
      102: { lat: 37.7749, lng: -122.4194, label: "Truck 102 - San Francisco" },
      103: { lat: 48.8566, lng: 2.3522, label: "Truck 103 - Paris" }
    };

    function traceLocation() {
      const id = document.getElementById('trackerId').value;
      const data = locations[id];

      if (data) {
        if (marker) {
          map.removeLayer(marker);
        }
        marker = L.marker([data.lat, data.lng]).addTo(map)
                 .bindPopup(`<strong>${data.label}</strong>`).openPopup();
        map.setView([data.lat, data.lng], 10);
      } else {
        alert("No location data found for ID " + id);
      }
    }
  </script>
</body>
</html>
