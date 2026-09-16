
var CACHE_NAME = "olfu-qms-v2";
var CACHE_URLS = [
  "/olfu-kiosk-qms/track.php",
  "/olfu-kiosk-qms/login.php",
  "/olfu-kiosk-qms/css/track.css",
  "/olfu-kiosk-qms/css/style.css",
  "/olfu-kiosk-qms/css/dashboard.css",
  "/olfu-kiosk-qms/scripts/campus_map.js",
  "/olfu-kiosk-qms/scripts/track.js",
  "/olfu-kiosk-qms/scripts/index.js",
  "/olfu-kiosk-qms/img/olfu-logo.png",
  "/olfu-kiosk-qms/map/MAP.svg",
  "/olfu-kiosk-qms/map/BUILDING.svg",
  "/olfu-kiosk-qms/map/ROAD.svg",
  "/olfu-kiosk-qms/map/PATH.svg",
  "/olfu-kiosk-qms/map/GATES.svg",
  "/olfu-kiosk-qms/api/track_status.php",
];

self.addEventListener("install", function (e) {
  e.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll(CACHE_URLS).catch(function (err) {
        console.warn("SW cache install partial:", err);
      });
    }),
  );
});

self.addEventListener("fetch", function (e) {
  var url = new URL(e.request.url);

  if (url.pathname.includes("/api/")) {
    e.respondWith(
      fetch(e.request)
        .then(function (response) {
          var clone = response.clone();
          caches.open(CACHE_NAME).then(function (cache) {
            cache.put(e.request, clone);
          });
          return response;
        })
        .catch(function () {
          return caches.match(e.request);
        }),
    );
    return;
  }

  e.respondWith(
    caches.match(e.request).then(function (cached) {
      return (
        cached ||
        fetch(e.request).then(function (response) {
          caches.open(CACHE_NAME).then(function (cache) {
            cache.put(e.request, response.clone());
          });
          return response;
        })
      );
    }),
  );
});

self.addEventListener("activate", function (e) {
  e.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys
          .filter(function (k) {
            return k !== CACHE_NAME;
          })
          .map(function (k) {
            return caches.delete(k);
          }),
      );
    }),
  );
});

self.addEventListener("push", function (e) {
  var data = e.data ? e.data.json() : {};
  self.registration.showNotification(data.title || "Queue Update", {
    body: data.body || "",
    icon: "/olfu-kiosk-qms/img/olfu-logo.png",
    badge: "/olfu-kiosk-qms/img/olfu-logo.png",
    tag: "queue-tracker",
    renotify: true,
  });
});

self.addEventListener("notificationclick", function (e) {
  e.notification.close();
  e.waitUntil(clients.openWindow("/olfu-kiosk-qms/"));
});
