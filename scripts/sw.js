self.addEventListener("push", (e) => {
  const data = e.data ? e.data.json() : {};
  self.registration.showNotification(data.title || "Queue Update", {
    body: data.body || "",
    icon: "/img/olfu-logo.png",
    badge: "/img/olfu-logo.png",
    tag: "queue-tracker",
    renotify: true,
  });
});

self.addEventListener("notificationclick", (e) => {
  e.notification.close();
  e.waitUntil(clients.openWindow("/"));
});
