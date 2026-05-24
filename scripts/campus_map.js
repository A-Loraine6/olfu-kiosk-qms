(function () {
  const CENTER = [14.619396479990225, 121.15188810883754];
  const ZOOM = 18;

  /* ── Building definitions ──
   * coords: polygon corners [lat, lng]
   * id: matches office_building field from API
   * directions: walking instruction from Gate 2 kiosk
   */
  const BUILDINGS = [
    {
      id: "VMS",
      name: "Vicente M. Santos (VMS) Building",
      short: "VMS Bldg.",
      offices: ["Admissions Office (1a)", "Registrar's Office (1b)"],
      color: "#1a8c3c",
      coords: [
        [14.61972409273912, 121.1516242540201],
        [14.619706017565886, 121.15175267892963],
        [14.619335476186539, 121.15167795898229],
        [14.619351291989904, 121.15155186907113],
      ],
      directions:
        "From Gate 2 kiosk → walk west along the main path → VMS Building is on your left.",
    },
    {
      id: "JSB",
      name: "Juliet Santos Building",
      short: "JSB",
      offices: [
        "Learning Resource Center (2a)",
        "Center for Guidance Services (2nd floor)",
      ],
      color: "#2255aa",
      coords: [
        [14.619653291524195, 121.15191641546697],
        [14.619605347035522, 121.15214292399482],
        [14.619297406049814, 121.15208902516456],
        [14.619350611135536, 121.15184360304394],
      ],
      directions:
        "From Gate 2 kiosk → JSB is directly to the east. Walk a few steps toward the building.",
    },
    {
      id: "SJBH",
      name: "St. John the Baptist Hall",
      short: "SJBH",
      offices: ["Student Financial Services (3a)"],
      color: "#7755aa",
      coords: [
        [14.619623451324301, 121.15227230026878],
        [14.619772937189579, 121.152682912584],
        [14.619656888960893, 121.15274592734525],
        [14.619468064593367, 121.15234954416967],
      ],
      directions:
        "From Gate 2 kiosk → head east through the corridor → SJBH is the building past JSB.",
    },
    {
      id: "SCH",
      name: "St. Catherine Hall",
      short: "SCH",
      offices: ["Learning Resource Center (4a)"],
      color: "#cc7733",
      coords: [
        [14.619936191379427, 121.15283943312552],
        [14.620132883006233, 121.15321142349032],
        [14.619908654541842, 121.15333338754841],
        [14.619711962714256, 121.15295123351247],
      ],
      directions:
        "From Gate 2 kiosk → walk east past SJBH → SCH is the next building after the small path corridor.",
    },
    {
      id: "SCH_COURT",
      name: "SCH Covered Court",
      short: "Covered Court",
      offices: [],
      color: "#cc9933",
      coords: [
        [14.62014654243454, 121.15321378434565],
        [14.620246463783992, 121.15339349234654],
        [14.620005095251761, 121.15352760279495],
        [14.619919448289465, 121.15334253037612],
      ],
      directions:
        "From Gate 2 kiosk → walk east past SCH → the Covered Court is adjacent.",
    },
    {
      id: "SPCB",
      name: "San Pedro Calungsod Building",
      short: "SPC Bldg.",
      offices: [],
      color: "#dd5533",
      coords: [
        [14.620415162066072, 121.15340019787071],
        [14.620517678649993, 121.15358929360313],
        [14.620175091399943, 121.15377436602195],
        [14.620075170018024, 121.15356649482688],
      ],
      directions:
        "From Gate 2 kiosk → take the main path all the way east past SCH and the Covered Court → SPC Building is at the far end near Gate 5.",
    },
    {
      id: "STUDENT_LOUNGE",
      name: "Student Lounge",
      short: "Student Lounge",
      offices: [],
      color: "#558844",
      coords: [
        [14.619789630954367, 121.15188474394203],
        [14.619841538238909, 121.15233267283979],
        [14.619776654131321, 121.15234474278013],
        [14.619728639879359, 121.1518887672555],
      ],
      directions:
        "From Gate 2 kiosk → walk slightly northeast → the Student Lounge is just above VMS Building.",
    },
    {
      id: "CANTEEN",
      name: "Canteen and Faculty Building",
      short: "Canteen / Faculty",
      offices: [],
      color: "#aa6633",
      coords: [
        [14.619767797100234, 121.1524255481257],
        [14.619878104848494, 121.1527074641096],
        [14.61980804452831, 121.15273365302613],
        [14.619702208683114, 121.15244557494424],
      ],
      directions:
        "From Gate 2 kiosk → walk east → the Canteen and Faculty Building is between Gates 2 and 3.",
    },
  ];

  /* ── Gate / kiosk markers ── */
  const GATES = [
    { id: "gate1", label: "Gate 1", coords: [14.619259486349115, 121.15159212436257] },
    {
      id: "gate2",
      label: "Gate 2 (Kiosk)",
      coords: [14.619219238820522, 121.15194028290007],
      isKiosk: true,
    },
    { id: "gate3", label: "Gate 3", coords: [14.619654418491558, 121.15277974000796] },
    { id: "gate4", label: "Gate 4", coords: [14.61991798557373, 121.15334687286543] },
    { id: "gate5", label: "Gate 5", coords: [14.620553298957223, 121.15381033627847] },
  ];

  /* ── Walkable path network ──
   * Array of polyline coordinate arrays tracing the dark-grey walkable zones
   */
  const WALK_PATHS = [
    // Main east-west spine along the front of buildings
    [
      [14.619321063150593, 121.15193688728144], // Gate 2 kiosk
      [14.619283645779579, 121.15209446168024],
      [14.619432747269885, 121.15232218589772],
      [14.619557745477412, 121.1523151075062],
      [14.619737537297093, 121.1527911293389], // Gate 3 area
      [14.620025203903074, 121.15340871900197],
      [14.61994643808522, 121.1534547285467],
      [14.61989335675725, 121.15336270945726], // Gate 4 area
      [14.620011505501992, 121.15356090441912],
      [14.62014164028506, 121.15350250769727],
      [14.62017074937165, 121.15359452678952],
      [14.62038649893297, 121.15348835091712], // Gate 5 area
      [14.62050122281974, 121.15375202100032]
    ],
    // Path from Gate 1 to Gate 2
    [
      [14.619254730002663, 121.15158244059504], // Gate 1
      [14.619236654790042, 121.15170269301527],
      [14.619359792146549, 121.1517353829936],
      [14.619321063150593, 121.15193688728144], // Gate 2
      [14.619219238820522, 121.15194028290007],
    ],
    // Internal corridor between VMS and JSB (north-south spur)
    [
      [14.6066, 121.14155],
      [14.60655, 121.1417],
      [14.60648, 121.14185],
      [14.6064, 121.14195],
    ],
    // Corridor between SJBH and SCH (small orange path on drawing)
    [
      [14.60625, 121.1426],
      [14.60632, 121.14263],
      [14.60638, 121.14268],
    ],
  ];

  /* ── Route segments: gate2 → each building ──
   * Reuses waypoints from WALK_PATHS
   */
  const ROUTES = {
    VMS: [
      [14.60638, 121.1415],
      [14.6064, 121.14135],
      [14.60645, 121.1412],
      [14.60675, 121.1413], // VMS entrance
    ],
    JSB: [
      [14.60638, 121.1415],
      [14.60636, 121.14175],
      [14.60648, 121.14192], // JSB entrance
    ],
    SJBH: [
      [14.60638, 121.1415],
      [14.60636, 121.14175],
      [14.60632, 121.1421],
      [14.6063, 121.14235], // SJBH entrance
    ],
    SCH: [
      [14.60638, 121.1415],
      [14.60636, 121.14175],
      [14.60632, 121.1421],
      [14.60628, 121.1424],
      [14.60625, 121.1426],
      [14.6063, 121.1428], // SCH entrance
    ],
    SCH_COURT: [
      [14.60638, 121.1415],
      [14.60636, 121.14175],
      [14.60632, 121.1421],
      [14.60628, 121.1424],
      [14.60625, 121.1426],
      [14.6063, 121.1428],
      [14.60638, 121.14315], // court entrance
    ],
    SPCB: [
      [14.60638, 121.1415],
      [14.60636, 121.14175],
      [14.60632, 121.1421],
      [14.60628, 121.1424],
      [14.60625, 121.1426],
      [14.60628, 121.14285],
      [14.60632, 121.1431],
      [14.60638, 121.1434],
      [14.60648, 121.14365],
      [14.6066, 121.1438], // SPC entrance
    ],
    STUDENT_LOUNGE: [
      [14.60638, 121.1415],
      [14.60648, 121.14155],
      [14.60662, 121.14162], // lounge entrance
    ],
    CANTEEN: [
      [14.60638, 121.1415],
      [14.60636, 121.14175],
      [14.60638, 121.142], // canteen entrance
    ],
  };

  /* ── State ── */
  let map = null;
  let userMarker = null;
  let activeRoute = null;
  let destBuilding = null; // building id from QMS
  let buildingLayers = {};
  let mapInitialized = false;

  /* ── Initialize map ── */
  function initMap() {
    if (mapInitialized) return;
    mapInitialized = true;

    map = L.map("campusMap", {
      center: CENTER,
      zoom: ZOOM,
      zoomControl: true,
      attributionControl: true,
    });

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
      maxZoom: 20,
    }).addTo(map);

    drawWalkPaths();
    drawBuildings();
    drawGates();
    startLocationTracking();

    // If destination already known, highlight immediately
    if (destBuilding) highlightRoute(destBuilding);
  }

  /* ── Draw walkable path network ── */
  function drawWalkPaths() {
    WALK_PATHS.forEach((coords) => {
      L.polyline(coords, {
        color: "#555",
        weight: 8,
        opacity: 0.35,
        lineCap: "round",
        lineJoin: "round",
      }).addTo(map);
    });
  }

  /* ── Draw buildings ── */
  function drawBuildings() {
    BUILDINGS.forEach((b) => {
      const layer = L.polygon(b.coords, {
        color: b.color,
        fillColor: b.color,
        fillOpacity: 0.55,
        weight: 2,
      }).addTo(map);

      layer.bindPopup(() => {
        const officeList = b.offices.length
          ? `<div style="margin-top:4px;font-size:11px;color:#5a7a68">${b.offices.join("<br>")}</div>`
          : "";
        const destBadge =
          destBuilding === b.id
            ? `<div class="popup-dest">📍 Your destination</div>`
            : "";
        return `<div class="popup-name">${b.name}</div>${officeList}${destBadge}`;
      });

      layer.on("click", () => layer.openPopup());
      buildingLayers[b.id] = layer;
    });
  }

  /* ── Draw gate markers ── */
  function drawGates() {
    GATES.forEach((g) => {
      const icon = L.divIcon({
        className: "",
        html: `<div style="
          background:${g.isKiosk ? "#006633" : "#e8a020"};
          color:#fff;
          font-family:'Plus Jakarta Sans',sans-serif;
          font-size:10px;
          font-weight:700;
          padding:3px 7px;
          border-radius:99px;
          white-space:nowrap;
          border:2px solid #fff;
          box-shadow:0 1px 4px rgba(0,0,0,.3);
        ">${g.isKiosk ? "📍 " + g.label : g.label}</div>`,
        iconAnchor: [0, 0],
      });
      L.marker(g.coords, { icon }).addTo(map);
    });
  }

  /* ── Highlight route to destination ── */
  function highlightRoute(buildingId) {
    // Remove previous route
    if (activeRoute) {
      map.removeLayer(activeRoute);
      activeRoute = null;
    }

    // Reset all building styles
    BUILDINGS.forEach((b) => {
      if (buildingLayers[b.id]) {
        buildingLayers[b.id].setStyle({
          fillOpacity: 0.55,
          weight: 2,
          color: b.color,
          fillColor: b.color,
        });
      }
    });

    const routeCoords = ROUTES[buildingId];
    const building = BUILDINGS.find((b) => b.id === buildingId);

    if (!routeCoords || !building) return;

    // Draw animated dashed route line
    activeRoute = L.polyline(routeCoords, {
      color: "#FFD700",
      weight: 5,
      opacity: 0.9,
      dashArray: "10, 8",
      lineCap: "round",
    }).addTo(map);

    // Highlight destination building
    buildingLayers[buildingId]?.setStyle({
      fillOpacity: 0.85,
      weight: 4,
      color: "#FFD700",
      fillColor: building.color,
    });

    // Update map header bar
    const destEl = document.getElementById("mapDestName");
    if (destEl) destEl.textContent = building.short;

    const chipEl = document.getElementById("mapWalkChip");
    if (chipEl) chipEl.style.display = "flex";

    const dirBar = document.getElementById("mapDirectionsBar");
    const dirTxt = document.getElementById("mapDirectionsText");
    if (dirBar && dirTxt) {
      dirTxt.textContent = building.directions;
      dirBar.style.display = "flex";
    }

    // Fit map to show route + destination
    const bounds = L.latLngBounds(routeCoords);
    map.fitBounds(bounds.pad(0.25));
  }

  /* ── User location tracking ── */
  function startLocationTracking() {
    if (!navigator.geolocation) return;

    const userIcon = L.divIcon({
      className: "",
      html: `<div style="
        width:18px;height:18px;
        background:#1a73e8;
        border-radius:50%;
        border:3px solid #fff;
        box-shadow:0 0 0 4px rgba(26,115,232,.25);
      "></div>`,
      iconSize: [18, 18],
      iconAnchor: [9, 9],
    });

    navigator.geolocation.watchPosition(
      (pos) => {
        const latlng = [pos.coords.latitude, pos.coords.longitude];
        if (!userMarker) {
          userMarker = L.marker(latlng, {
            icon: userIcon,
            zIndexOffset: 1000,
          }).addTo(map);
        } else {
          userMarker.setLatLng(latlng);
        }
      },
      () => {}, // silently ignore errors
      { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 },
    );
  }

  /* ── Map building ID from office/building name ──
   * Called by track.js after fetching ticket data
   */
  function resolveBuildingId(officeName, buildingHint) {
    const name = (officeName + " " + (buildingHint || "")).toLowerCase();
    if (
      name.includes("registrar") ||
      name.includes("admission") ||
      name.includes("vms") ||
      name.includes("vicente")
    )
      return "VMS";
    if (
      name.includes("jsb") ||
      name.includes("juliet") ||
      name.includes("lrc") ||
      name.includes("guidance")
    )
      return "JSB";
    if (
      name.includes("sjbh") ||
      name.includes("john") ||
      name.includes("financial")
    )
      return "SJBH";
    if (
      name.includes("sch") ||
      (name.includes("catherine") && !name.includes("court"))
    )
      return "SCH";
    if (name.includes("court") || name.includes("covered")) return "SCH_COURT";
    if (
      name.includes("spc") ||
      name.includes("calungsod") ||
      name.includes("pedro")
    )
      return "SPCB";
    if (name.includes("lounge")) return "STUDENT_LOUNGE";
    if (name.includes("canteen") || name.includes("faculty")) return "CANTEEN";
    return null;
  }

  /* ── Public API ── */
  window.CampusMap = {
    init: initMap,

    /* Call this from track.js once ticket data loads */
    setDestination(officeName, buildingHint) {
      const id = resolveBuildingId(officeName, buildingHint);
      destBuilding = id;
      if (id && mapInitialized) highlightRoute(id);
    },
  };
})();
