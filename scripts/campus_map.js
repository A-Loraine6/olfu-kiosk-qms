(function () {
  "use strict";

  const CAL = {
    gpsA: { lat: 14.619259, lng: 121.151592 },
    svgA: { x: 608, y: 791 },
    gpsB: { lat: 14.620553, lng: 121.15381 },
    svgB: { x: 3660, y: 596 },
  };

  const SCALE_X = (CAL.svgB.x - CAL.svgA.x) / (CAL.gpsB.lng - CAL.gpsA.lng);
  const SCALE_Y = (CAL.svgB.y - CAL.svgA.y) / (CAL.gpsB.lat - CAL.gpsA.lat);

  function gpsToSvg(lat, lng) {
    return {
      x: CAL.svgA.x + (lng - CAL.gpsA.lng) * SCALE_X,
      y: CAL.svgA.y + (lat - CAL.gpsA.lat) * SCALE_Y,
    };
  }

  const NODES = [
    { id: "gate1", x: 608, y: 791 },
    { id: "gate2", x: 830, y: 879 },
    { id: "gate3", x: 2225, y: 850 },
    { id: "gate4", x: 2980, y: 980 },
    { id: "gate5", x: 3700, y: 596 },
    { id: "j_1", x: 700, y: 693 },
    { id: "j_2", x: 800, y: 550 },
    { id: "j_3", x: 730, y: 493 },
    { id: "j_4", x: 950, y: 380 },
    { id: "j_5", x: 980, y: 330 },
    { id: "j_6", x: 1032, y: 328 },
    { id: "j_7", x: 1360, y: 420 },
    { id: "j_8", x: 1330, y: 470 },
    { id: "j_9", x: 1500, y: 460 },
    { id: "j_10", x: 1270, y: 750 },
    { id: "j_11", x: 1160, y: 890 },
    { id: "j_12", x: 1120, y: 920 },
    { id: "j_13", x: 1060, y: 940 },
    { id: "j_14", x: 1000, y: 930 },
    { id: "j_15", x: 850, y: 820 },
    { id: "j_16", x: 1550, y: 480 },
    { id: "j_17", x: 1600, y: 530 },
    { id: "j_18", x: 1650, y: 600 },
    { id: "j_19", x: 1700, y: 650 },
    { id: "j_20", x: 1745, y: 655 },
    { id: "j_21", x: 2101, y: 683 },
    { id: "j_22", x: 2063, y: 760 },
    { id: "j_24", x: 2263, y: 745 },
    { id: "j_25", x: 2456, y: 772 },
    { id: "j_26", x: 2929, y: 782 },
    { id: "j_27", x: 3300, y: 783 },
    { id: "j_28", x: 3194, y: 783 },
    { id: "j_29", x: 3300, y: 596 },
    { id: "b_vms", x: 610, y: 443 },
    { id: "1a", x: 526, y: 530 },
    { id: "1b", x: 690, y: 300 },
    { id: "b_jsb", x: 1168, y: 654 },
    { id: "2a", x: 1050, y: 554 },
    { id: "b_sjbh", x: 1735, y: 760 },
    { id: "3a", x: 1735, y: 830 },
    { id: "b_sch", x: 2687, y: 784 },
    { id: "b_spcb", x: 3300, y: 639 },
    { id: "b_canteen", x: 1860, y: 580 },
    { id: "b_lounge", x: 1300, y: 344 },
  ];

  const NODE_MAP = {};
  NODES.forEach((n) => {
    NODE_MAP[n.id] = n;
  });

  const EDGES = [
    ["gate1", "j_1"],
    ["j_1", "j_2"],
    ["j_2", "j_3"],
    ["j_3", "b_vms"],
    ["b_vms", "1a"],
    ["b_vms", "1b"],
    ["j_2", "j_4"],
    ["j_4", "j_5"],
    ["j_5", "j_6"],
    ["j_6", "j_7"],
    ["j_7", "b_lounge"],
    ["j_7", "j_8"],
    ["j_8", "b_jsb"],
    ["b_jsb", "2a"],
    ["j_7", "j_9"],
    ["j_9", "j_16"],
    ["j_9", "j_10"],
    ["j_1", "j_15"],
    ["gate2", "j_15"],
    ["j_15", "j_14"],
    ["j_14", "j_13"],
    ["j_13", "j_12"],
    ["j_12", "j_11"],
    ["j_11", "j_10"],
    ["j_16", "j_17"],
    ["j_17", "j_18"],
    ["j_18", "j_19"],
    ["j_19", "j_20"],
    ["j_20", "j_21"],
    ["j_20", "b_canteen"],
    ["j_10", "b_sjbh"],
    ["j_20", "b_sjbh"],
    ["b_sjbh", "3a"],
    ["j_21", "j_24"],
    ["j_22", "b_sjbh"],
    ["j_23", "gate3"],
    ["j_23", "b_sjbh"],
    ["j_22", "j_24"],
    ["gate3", "j_24"],
    ["j_24", "j_25"],
    ["j_25", "b_sch"],
    ["j_25", "j_26"],
    ["j_26", "j_28"],
    ["j_26", "b_sch"],
    ["gate4", "j_26"],
    ["j_27", "j_28"],
    ["j_27", "j_29"],
    ["j_28", "j_27"],
    ["j_28", "j_26"],
    ["j_29", "j_27"],
    ["j_29", "gate5"],
  ];

  const BUILDING_LABELS = [
    { id: "VMS", label: "VMS", x: 626, y: 390 },
    { id: "JSB", label: "JSB", x: 1168, y: 595 },
    { id: "SJBH", label: "SJBH", x: 1735, y: 735 },
    { id: "SCH", label: "SCH", x: 2687, y: 725 },
    { id: "SPCB", label: "SPCB", x: 3300, y: 580 },
    { id: "CANTEEN", label: "Canteen", x: 1930, y: 590 },
    { id: "LOUNGE", label: "Lounge", x: 1300, y: 284 },
    { id: "FUMC", label: "Fatima University Medical Center", x: 1550, y: 1090 },
    { id: "AVM", label: "Antipolo Valley Mall", x: 1550, y: 1590 },
  ];

  const GATE_LABELS = [
    { id: "gate1", label: "Gate 1", x: 510, y: 770 },
    { id: "gate2", label: "Gate 2", x: 800, y: 900 },
    { id: "gate3", label: "Gate 3", x: 2225, y: 860 },
    { id: "gate4", label: "Gate 4", x: 2980, y: 980 },
    { id: "gate5", label: "Gate 5", x: 3750, y: 680 },
  ];

  const SUBOFFICE_LABELS = [
    { id: "1a", label: "Admissions Office", x: 580, y: 600 },
    { id: "1b", label: "Registrar's Office", x: 790, y: 350 },
    { id: "2a", label: "Learning Resource Center", x: 1050, y: 554 },
    { id: "3a", label: "Student Financial Services", x: 1670, y: 820 },
    { id: "4a", label: "Learning Resource Center", x: 2565, y: 790 },
  ];

  function dist(a, b) {
    const dx = a.x - b.x,
      dy = a.y - b.y;
    return Math.sqrt(dx * dx + dy * dy);
  }

  function buildGraph() {
    const adj = {};
    NODES.forEach((n) => {
      adj[n.id] = [];
    });
    EDGES.forEach(([a, b]) => {
      const na = NODE_MAP[a],
        nb = NODE_MAP[b];
      if (!na || !nb) return;
      const d = dist(na, nb);
      adj[a].push({ id: b, cost: d });
      adj[b].push({ id: a, cost: d });
    });
    return adj;
  }

  const GRAPH = buildGraph();

  function aStar(startId, goalId) {
    if (startId === goalId) return [startId];
    const goal = NODE_MAP[goalId];
    if (!goal || !NODE_MAP[startId]) return null;

    const open = new Map();
    const closed = new Set();
    const parent = new Map();

    open.set(startId, { g: 0, f: dist(NODE_MAP[startId], goal) });
    parent.set(startId, null);

    while (open.size > 0) {
      let curr = null,
        best = Infinity;
      open.forEach((v, k) => {
        if (v.f < best) {
          best = v.f;
          curr = k;
        }
      });

      if (curr === goalId) {
        const path = [];
        let node = curr;
        while (node !== null) {
          path.unshift(node);
          node = parent.get(node) ?? null;
          if (path.length > 300) break;
        }
        return path;
      }

      const currData = open.get(curr);
      open.delete(curr);
      closed.add(curr);

      for (const neighbor of GRAPH[curr] || []) {
        if (closed.has(neighbor.id)) continue;
        const g = currData.g + neighbor.cost;
        const existing = open.get(neighbor.id);
        if (!existing || g < existing.g) {
          open.set(neighbor.id, {
            g,
            f: g + dist(NODE_MAP[neighbor.id], goal),
          });
          parent.set(neighbor.id, curr);
        }
      }
    }
    return null;
  }

  const NODE_SNAP_HYSTERESIS = 60;

  function nearestNode(svgX, svgY, preferId) {
    let best = null,
      bestD = Infinity;
    NODES.forEach((n) => {
      const d = dist({ x: svgX, y: svgY }, n);
      if (d < bestD) {
        bestD = d;
        best = n;
      }
    });

    if (preferId) {
      const preferNode = NODE_MAP[preferId];
      if (preferNode && preferNode.id !== best.id) {
        const preferD = dist({ x: svgX, y: svgY }, preferNode);
        if (preferD - bestD < NODE_SNAP_HYSTERESIS) {
          return preferNode;
        }
      }
    }
    return best;
  }

  const BUILDING_NODE = {
    VMS: "b_vms",
    ADMISSIONS: "1a",
    REGISTRAR: "1b",
    JSB: "b_jsb",
    SJBH: "b_sjbh",
    ACCOUNTING: "3a",
    SCH: "b_sch",
    SCH_COURT: "b_sch",
    SPCB: "b_spcb",
    STUDENT_LOUNGE: "b_lounge",
    CANTEEN: "b_canteen",
  };

  const DEST_PARENT_BUILDING = {
    ADMISSIONS: "VMS",
    REGISTRAR: "VMS",
    ACCOUNTING: "SJBH",
  };

  const BUILDING_INFO = {
    VMS: { name: "Vicente M. Santos Building", short: "VMS" },
    ADMISSIONS: { name: "Admissions Office (VMS)", short: "Admissions" },
    REGISTRAR: { name: "Registrar's Office (VMS)", short: "Registrar" },
    JSB: { name: "Juliet Santos Building", short: "JSB" },
    SJBH: { name: "St. John the Baptist Hall", short: "SJBH" },
    ACCOUNTING: {
      name: "Student Financial Services (SJBH)",
      short: "Accounting",
    },
    SCH: { name: "St. Catherine Hall", short: "SCH" },
    SCH_COURT: { name: "SCH Covered Court", short: "Covered Court" },
    SPCB: { name: "San Pedro Calungsod Bldg", short: "SPCB" },
    STUDENT_LOUNGE: { name: "Student Lounge", short: "Student Lounge" },
    CANTEEN: { name: "Canteen / Faculty", short: "Canteen" },
  };

  function resolveBuildingId(officeName, buildingHint) {
    const s = (officeName + " " + (buildingHint || "")).toLowerCase();

    if (/registrar/i.test(s)) return "REGISTRAR";
    if (/admission|admissions|enrollment/i.test(s)) return "ADMISSIONS";
    if (
      /accounting|account|balance|financial\s+services|cashier|scholarship|billing|finance/i.test(
        s,
      )
    )
      return "ACCOUNTING";

    if (/vms|vicente|santos\s+build/i.test(s)) return "VMS";
    if (/jsb|juliet|lrc|library|resource\s+center|guidance|counseling/i.test(s))
      return "JSB";
    if (/(sjbh|john|baptist|accounting|accountancy|finance)/i.test(s)) {
      return "SJBH";
    }
    if (/court|covered\s+court/i.test(s)) return "SCH_COURT";
    if (/sch|catherine|clinic|health|infirmary/i.test(s)) return "SCH";
    if (/spc[ab]|calungsod|pedro|engineering|it\s+dept|computer\s+lab/i.test(s))
      return "SPCB";
    if (/lounge|student\s+center|student\s+affairs/i.test(s))
      return "STUDENT_LOUNGE";
    if (/canteen|faculty|cafeteria|food/i.test(s)) return "CANTEEN";
    return null;
  }

  const SVG_W = 4059;
  const SVG_H = 2649;
  const CAMPUS = { x: 59, y: 1, w: 4000, h: 2500 };
  const MOBILE_USER_AGENT = /Mobi|Android|iPhone|iPad|iPod|Windows Phone|Opera Mini|IEMobile/i;

  let overlayEl = null;
  let clipWrapEl = null;
  let mapWrapEl = null;
  let userDot = null;
  let pathLine = null;
  let destMarker = null;
  let popupEl = null;
  let compassEl = null;
  let labelGroupEl = null;
  let watchId = null;
  let currentUserNode = null;
  let destBuildingId = null;
  let mapInitialized = false;
  let MIN_SCALE = 0.08;
  let gpsErrorShown = false;
  let deviceHeadingActive = false; 
  let isMobileDevice = false;
  let lowPowerMode = false;

  let viewX = 0,
    viewY = 0,
    viewScale = 1;
  let viewAngle = 0; 
  let isPanning = false,
    panStartX = 0,
    panStartY = 0,
    panOriginX = 0,
    panOriginY = 0;

  let isRotating = false,
    rotateStartX = 0,
    rotateStartAngle = 0;

  let firstFix = true;
  let autoCenterOnLocation = true;
  let disableDeviceOrientation = true;

  let labelItems = [];
  let poiItems = [];
  let transformScheduled = false;

  let smoothAngle = 0;
  const HEADING_ALPHA = 0.08;
  const METERS_PER_PIXEL = 0.045; 

  const BUILDINGS_INFO = [
    {
      id: "VMS",
      name: "Vicente M. Santos Building",
      short: "VMS",
      offices: ["Admissions Office", "Registrar's Office"],
      nodeId: "b_vms",
      cx: 636,
      cy: 443,
      rx: 200,
      ry: 180,
      icon: "fa-file-signature",
      category: "Admissions & Records",
      color: "#0a6635",
    },
    {
      id: "JSB",
      name: "Juliet Santos Building",
      short: "JSB",
      offices: [
        "Learning Resource Center",
        "2nd Floor: Center for Guidance Services",
      ],
      nodeId: "b_jsb",
      cx: 1168,
      cy: 654,
      rx: 185,
      ry: 165,
      icon: "fa-book-open",
      category: "Library & Guidance",
      color: "#0a6635",
    },
    {
      id: "SJBH",
      name: "St. John the Baptist Hall",
      short: "SJBH",
      offices: ["Student Financial Services"],
      nodeId: "b_sjbh",
      cx: 1805,
      cy: 793,
      rx: 160,
      ry: 120,
      icon: "fa-money-check-dollar",
      category: "Finance & Billing",
      color: "#0a6635",
    },
    {
      id: "SCH",
      name: "St. Catherine Hall",
      short: "SCH",
      offices: ["Learning Resource Center"],
      nodeId: "b_sch",
      cx: 2687,
      cy: 784,
      rx: 220,
      ry: 120,
      icon: "fa-staff-snake",
      category: "Health Services",
      color: "#0a6635",
    },
    {
      id: "SPCB",
      name: "San Pedro Calungsod Building",
      short: "SPCB",
      offices: [],
      nodeId: "b_spcb",
      cx: 3284,
      cy: 639,
      rx: 185,
      ry: 185,
      icon: "fa-laptop-code",
      category: "Engineering & IT",
      color: "#0a6635",
    },
    {
      id: "CANTEEN",
      name: "Canteen / Faculty Building",
      short: "Canteen",
      offices: ["Canteen", "Faculty Offices"],
      nodeId: "b_canteen",
      cx: 1860,
      cy: 610,
      rx: 150,
      ry: 100,
      icon: "fa-utensils",
      category: "Dining & Faculty",
      color: "#0a6635",
    },
  ];

  let activePopupSVG = null;
  let userSvgX = null,
    userSvgY = null;

  function mapIsVisible() {
    return !!(
      mapWrapEl &&
      mapWrapEl.clientWidth > 0 &&
      mapWrapEl.clientHeight > 0
    );
  }

  function scheduleTransform() {
    if (transformScheduled) return;
    transformScheduled = true;
    requestAnimationFrame(() => {
      transformScheduled = false;
      applyTransform();
    });
  }

  function svgToScreen(svgX, svgY) {
    const cw = mapWrapEl.clientWidth;
    const ch = mapWrapEl.clientHeight;
    const localX = svgX - CAMPUS.x;
    const localY = svgY - CAMPUS.y;
    const mx = viewX + localX * viewScale;
    const my = viewY + localY * viewScale;
    const dx = mx - cw / 2;
    const dy = my - ch / 2;
    const cos = Math.cos(viewAngle);
    const sin = Math.sin(viewAngle);
    return {
      x: cw / 2 + dx * cos - dy * sin,
      y: ch / 2 + dx * sin + dy * cos,
    };
  }

  function applyTransform() {
    if (!mapIsVisible()) return;
    const wrap = document.getElementById("mapClipWrap");
    if (!wrap) return;
    const cw = mapWrapEl.clientWidth;
    const ch = mapWrapEl.clientHeight;
    const deg = (viewAngle * 180) / Math.PI;

    wrap.style.transformOrigin = "0 0";
    wrap.style.transform =
      `translate(${cw / 2}px,${ch / 2}px)` +
      `rotate(${deg}deg)` +
      `translate(${viewX - cw / 2}px,${viewY - ch / 2}px)` +
      `scale(${viewScale})`;

    updateLabelRotations();
    updateUserDotHeading();
    updateCompass();
    updatePinScaling();
    updateActivePopupPosition();
  }

  function updateActivePopupPosition() {
    if (!activePopupSVG || !popupEl || popupEl.style.display === "none") return;
    const s = svgToScreen(activePopupSVG.x, activePopupSVG.y);
    positionPopup(s.x, s.y);
  }

  function updateLabelRotations() {
    if (!labelItems.length) return;
    const deg = -((viewAngle * 180) / Math.PI);
    labelItems.forEach(({ g, cx, cy }) => {
      g.setAttribute("transform", `rotate(${deg},${cx},${cy})`);
    });
  }

  function updateCompass() {
    renderCompassNeedle();
  }

  function updateUserDotHeading() {
    if (!userDot) return;
    const headingG = userDot.querySelector(".dot-heading");
    if (!headingG) return;
    const deg = -((viewAngle * 180) / Math.PI);
    headingG.setAttribute("transform", `rotate(${deg})`);
  }

  function updatePinScaling() {
    if (!poiItems.length) return;
    const target = Math.min(
      1.6,
      Math.max(0.55, 1 / Math.sqrt(viewScale / MIN_SCALE)),
    );
    poiItems.forEach(({ g, cx, cy }) => {
      g.setAttribute(
        "transform",
        `translate(${cx},${cy}) scale(${target}) translate(${-cx},${-cy}) translate(${cx},${cy})`,
      );
    });
  }

  function clampPan() {
    if (!mapIsVisible()) return;
    const cw = mapWrapEl.clientWidth;
    const ch = mapWrapEl.clientHeight;

    const cos = Math.cos(viewAngle);
    const sin = Math.sin(viewAngle);

    const corners = [
      { x: 0, y: 0 },
      { x: CAMPUS.w, y: 0 },
      { x: CAMPUS.w, y: CAMPUS.h },
      { x: 0, y: CAMPUS.h },
    ];

    const screenCorners = corners.map(({ x, y }) => {
      const mx = viewX + x * viewScale;
      const my = viewY + y * viewScale;
      const dx = mx - cw / 2;
      const dy = my - ch / 2;
      return {
        sx: cw / 2 + dx * cos - dy * sin,
        sy: ch / 2 + dx * sin + dy * cos,
      };
    });

    const minSX = Math.min(...screenCorners.map((c) => c.sx));
    const maxSX = Math.max(...screenCorners.map((c) => c.sx));
    const minSY = Math.min(...screenCorners.map((c) => c.sy));
    const maxSY = Math.max(...screenCorners.map((c) => c.sy));

    let shiftX = 0,
      shiftY = 0;
    if (minSX > 0) shiftX = -minSX;
    if (maxSX < cw) shiftX = cw - maxSX;
    if (minSY > 0) shiftY = -minSY;
    if (maxSY < ch) shiftY = ch - maxSY;

    if (shiftX === 0 && shiftY === 0) return;

    viewX += shiftX * cos + shiftY * sin;
    viewY += -shiftX * sin + shiftY * cos;
  }

  let poiGroupEl = null;
  let locateBtnEl = null;
  let searchInputEl = null;
  let searchResultsEl = null;
  let locatingToastEl = null;

  function initMap() {
    if (mapInitialized) return;
    mapInitialized = true;

    mapWrapEl = document.getElementById("campusMap");
    if (!mapWrapEl) return;

    isMobileDevice =
      typeof navigator !== "undefined" &&
      MOBILE_USER_AGENT.test(navigator.userAgent);
    lowPowerMode = false;

    mapWrapEl.style.cssText = `
      position:absolute; inset:0;
      overflow:hidden; background:#fff;
      touch-action:none; cursor:grab;
      ${lowPowerMode ? "" : "will-change: transform;"}
    `;

    const clipWrap = document.createElement("div");
    clipWrap.id = "mapClipWrap";
    clipWrap.style.cssText = `
      position:absolute; top:0; left:0;
      width:${CAMPUS.w}px; height:${CAMPUS.h}px;
      transform-origin:0 0;
      ${lowPowerMode ? "" : "will-change: transform;"}
    `;
    mapWrapEl.appendChild(clipWrap);
    clipWrapEl = clipWrap;

    const img = document.createElement("img");
    img.src = "map/MAP.svg";
    img.alt = "Campus map";
    img.style.cssText = `
      position:absolute;
      top:${-CAMPUS.y}px; left:${-CAMPUS.x}px;
      width:${SVG_W}px; height:${SVG_H}px;
      pointer-events:none; user-select:none;
      ${lowPowerMode ? "" : "filter: saturate(1.08) contrast(1.03);"}
    `;
    img.id = "mapImg";
    clipWrap.appendChild(img);

    overlayEl = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    overlayEl.setAttribute(
      "viewBox",
      `${CAMPUS.x} ${CAMPUS.y} ${CAMPUS.w} ${CAMPUS.h}`,
    );
    overlayEl.style.cssText = `
      position:absolute; top:0; left:0;
      width:${CAMPUS.w}px; height:${CAMPUS.h}px;
      pointer-events:all;
    `;
    overlayEl.id = "mapOverlay";
    clipWrap.appendChild(overlayEl);

    overlayEl.innerHTML = `
      <defs>
        <filter id="glow">
          <feGaussianBlur stdDeviation="8" result="blur"/>
          <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
        </filter>
        <filter id="shadow">
          <feDropShadow dx="0" dy="2" stdDeviation="4" flood-opacity="0.3"/>
        </filter>
        <filter id="pinShadow">
          <feDropShadow dx="0" dy="3" stdDeviation="3" flood-opacity="0.35"/>
        </filter>
        <filter id="labelShadow">
          <feDropShadow dx="0" dy="1" stdDeviation="2" flood-opacity="0.45"/>
        </filter>
        <radialGradient id="dotConeGrad" cx="50%" cy="100%" r="100%">
          <stop offset="0%" stop-color="#1a73e8" stop-opacity="0.55"/>
          <stop offset="100%" stop-color="#1a73e8" stop-opacity="0"/>
        </radialGradient>
        <style>
          .pulse-ring { animation: none; }
          .path-line { stroke-dasharray: 24 16; ${lowPowerMode ? "animation: none;" : "animation: dashFlow 0.8s linear infinite;"} }
          @keyframes dashFlow { to { stroke-dashoffset: -40; } }
          .pin-drop { ${lowPowerMode ? "animation: none;" : "animation: pinDrop 0.55s cubic-bezier(.34,1.56,.64,1) both;"} }
          @keyframes pinDrop {
            0% { transform: translateY(-120px) scale(0.6); opacity:0; }
            60% { transform: translateY(8px) scale(1.05); opacity:1; }
            80% { transform: translateY(-4px) scale(0.98); }
            100% { transform: translateY(0) scale(1); }
          }
          .poi-halo { ${lowPowerMode ? "animation: none;" : "animation: poiPulseHalo 2.4s ease-in-out infinite;"} }
          @keyframes poiPulseHalo {
            0%,100% { opacity:0.18; }
            50% { opacity:0.4; }
          }
        </style>
      </defs>
    `;

    const pathCasing = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "polyline",
    );
    pathCasing.id = "pathCasing";
    pathCasing.setAttribute("fill", "none");
    pathCasing.setAttribute("stroke", "#8a6a00");
    pathCasing.setAttribute("stroke-width", "24");
    pathCasing.setAttribute("stroke-linecap", "round");
    pathCasing.setAttribute("stroke-linejoin", "round");
    pathCasing.setAttribute("opacity", "0");
    overlayEl.appendChild(pathCasing);

    pathLine = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "polyline",
    );
    pathLine.classList.add("path-line");
    pathLine.setAttribute("fill", "none");
    pathLine.setAttribute("stroke", "#FFD600");
    pathLine.setAttribute("stroke-width", "18");
    pathLine.setAttribute("stroke-linecap", "round");
    pathLine.setAttribute("stroke-linejoin", "round");
    pathLine.setAttribute("opacity", "0");
    if (!lowPowerMode) {
      pathLine.setAttribute("filter", "url(#glow)");
    }
    overlayEl.appendChild(pathLine);

    destMarker = document.createElementNS("http://www.w3.org/2000/svg", "g");
    destMarker.setAttribute("opacity", "0");
    destMarker.innerHTML = `
      <circle r="28" fill="#0a6635" ${lowPowerMode ? "" : "filter=\"url(#shadow)\""}/>
      <circle r="20" fill="#00c45e"/>
      <text y="7" text-anchor="middle" font-size="22" fill="white">📍</text>
    `;
    overlayEl.appendChild(destMarker);

    userDot = document.createElementNS("http://www.w3.org/2000/svg", "g");
    userDot.setAttribute("opacity", "0");
    userDot.style.cursor = "pointer";
    userDot.innerHTML = `
      <circle class="dot-halo" r="42" fill="#1a73e8" opacity="0.13"/>
      <g class="dot-heading" opacity="0">
        <path d="M0,-8 L-16,-46 A18,18 0 0 1 16,-46 Z" fill="url(#dotConeGrad)"/>
      </g>
      <circle class="pulse-ring" r="14" fill="#1a73e8" opacity="0.35"/>
      <circle r="11" fill="#1a73e8" stroke="#ffffff" stroke-width="3" ${lowPowerMode ? "" : "filter=\"url(#shadow)\""}/>
    `;
    userDot.addEventListener("click", (e) => {
      e.stopPropagation();
      showYouAreHerePopup(e);
    });
    userDot.addEventListener("touchend", (e) => {
      e.stopPropagation();
      showYouAreHerePopup(e.changedTouches[0]);
    });
    overlayEl.appendChild(userDot);

    labelGroupEl = document.createElementNS("http://www.w3.org/2000/svg", "g");
    labelGroupEl.id = "labelGroup";
    overlayEl.appendChild(labelGroupEl);

    BUILDING_LABELS.forEach((b) => {
      const PAD_X = 30,
        R = 22;
      const approxW = b.label.length * 28 + PAD_X * 2;
      const approxH = 52;

      const g = document.createElementNS("http://www.w3.org/2000/svg", "g");
      g.setAttribute("data-cx", b.x);
      g.setAttribute("data-cy", b.y);

      const pill = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "rect",
      );
      pill.setAttribute("x", b.x - approxW / 2);
      pill.setAttribute("y", b.y - approxH / 2);
      pill.setAttribute("width", approxW);
      pill.setAttribute("height", approxH);
      pill.setAttribute("rx", R);
      pill.setAttribute("ry", R);
      pill.setAttribute("fill", "rgba(255,255,255,0.92)");
      if (!lowPowerMode) {
        pill.setAttribute("filter", "url(#labelShadow)");
      }
      g.appendChild(pill);

      const t = document.createElementNS("http://www.w3.org/2000/svg", "text");
      t.setAttribute("x", b.x);
      t.setAttribute("y", b.y);
      t.setAttribute("text-anchor", "middle");
      t.setAttribute("dominant-baseline", "central");
      t.setAttribute("font-size", "34");
      t.setAttribute("font-weight", "800");
      t.setAttribute("font-family", "'Plus Jakarta Sans', sans-serif");
      t.setAttribute("fill", "#0a4d2b");
      t.setAttribute("pointer-events", "none");
      t.textContent = b.label;
      g.appendChild(t);

      labelGroupEl.appendChild(g);
      labelItems.push({ g, cx: b.x, cy: b.y });
    });

    GATE_LABELS.forEach((gt) => {
      const PAD_X = 16,
        R = 14;
      const approxW = gt.label.length * 15 + PAD_X * 2;
      const approxH = 30;

      const g = document.createElementNS("http://www.w3.org/2000/svg", "g");
      g.setAttribute("data-cx", gt.x);
      g.setAttribute("data-cy", gt.y);

      const pill = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "rect",
      );
      pill.setAttribute("x", gt.x - approxW / 2);
      pill.setAttribute("y", gt.y - approxH / 2);
      pill.setAttribute("width", approxW);
      pill.setAttribute("height", approxH);
      pill.setAttribute("rx", R);
      pill.setAttribute("ry", R);
      pill.setAttribute("fill", "rgba(255,255,255,0.92)");
      pill.setAttribute("stroke", "#0a6635");
      pill.setAttribute("stroke-width", "2");
      if (!lowPowerMode) {
        pill.setAttribute("filter", "url(#labelShadow)");
      }
      g.appendChild(pill);

      const t = document.createElementNS("http://www.w3.org/2000/svg", "text");
      t.setAttribute("x", gt.x);
      t.setAttribute("y", gt.y);
      t.setAttribute("text-anchor", "middle");
      t.setAttribute("dominant-baseline", "central");
      t.setAttribute("font-size", "18");
      t.setAttribute("font-weight", "800");
      t.setAttribute("font-family", "'Plus Jakarta Sans', sans-serif");
      t.setAttribute("fill", "#0a6635");
      t.setAttribute("pointer-events", "none");
      t.textContent = gt.label;
      g.appendChild(t);

      labelGroupEl.appendChild(g);
      labelItems.push({ g, cx: gt.x, cy: gt.y });
    });

    SUBOFFICE_LABELS.forEach((sb) => {
      const PAD_X = 12,
        R = 12;
      const approxW = sb.label.length * 8.5 + PAD_X * 2;
      const approxH = 24;

      const g = document.createElementNS("http://www.w3.org/2000/svg", "g");
      g.setAttribute("data-cx", sb.x);
      g.setAttribute("data-cy", sb.y);

      const pill = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "rect",
      );
      pill.setAttribute("x", sb.x - approxW / 2);
      pill.setAttribute("y", sb.y - approxH / 2);
      pill.setAttribute("width", approxW);
      pill.setAttribute("height", approxH);
      pill.setAttribute("rx", R);
      pill.setAttribute("ry", R);
      pill.setAttribute("fill", "rgba(255,255,255,0.95)");
      pill.setAttribute("stroke", "#0a6635");
      pill.setAttribute("stroke-width", "1.5");
      if (!lowPowerMode) {
        pill.setAttribute("filter", "url(#labelShadow)");
      }
      g.appendChild(pill);

      const t = document.createElementNS("http://www.w3.org/2000/svg", "text");
      t.setAttribute("x", sb.x);
      t.setAttribute("y", sb.y);
      t.setAttribute("text-anchor", "middle");
      t.setAttribute("dominant-baseline", "central");
      t.setAttribute("font-size", "13");
      t.setAttribute("font-weight", "700");
      t.setAttribute("font-family", "'Plus Jakarta Sans', sans-serif");
      t.setAttribute("fill", "#0a6635");
      t.setAttribute("pointer-events", "none");
      t.textContent = sb.label;
      g.appendChild(t);

      labelGroupEl.appendChild(g);
      labelItems.push({ g, cx: sb.x, cy: sb.y });
    });

    poiGroupEl = document.createElementNS("http://www.w3.org/2000/svg", "g");
    poiGroupEl.id = "poiGroup";
    overlayEl.appendChild(poiGroupEl);

    BUILDINGS_INFO.forEach((b, i) => {
      const pinX = b.cx;
      const pinY = b.cy - b.ry - 34;
      const g = document.createElementNS("http://www.w3.org/2000/svg", "g");
      g.setAttribute("data-pin", b.id);
      g.setAttribute("data-cx", pinX);
      g.setAttribute("data-cy", pinY);
      g.setAttribute("transform", `translate(${pinX},${pinY})`);
      g.style.cursor = "pointer";
      if (!lowPowerMode) {
        g.classList.add("pin-drop");
        g.style.animationDelay = `${i * 0.05}s`;
      }
      g.innerHTML = ``;
      g.addEventListener("click", (e) => {
        e.stopPropagation();
        showBuildingPopup(b, e);
      });
      g.addEventListener("touchend", (e) => {
        e.stopPropagation();
        showBuildingPopup(b, e.changedTouches[0]);
      });
      poiGroupEl.appendChild(g);
      poiItems.push({ g, cx: pinX, cy: pinY });

      const hit = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "ellipse",
      );
      hit.setAttribute("cx", b.cx);
      hit.setAttribute("cy", b.cy);
      hit.setAttribute("rx", b.rx);
      hit.setAttribute("ry", b.ry);
      hit.setAttribute("fill", "transparent");
      hit.setAttribute("stroke", "none");
      hit.setAttribute("focusable", "false");
      hit.setAttribute("tabindex", "-1");
      hit.style.outline = "none";
      hit.style.border = "none";
      hit.style.cursor = "pointer";
      hit.addEventListener("click", (e) => {
        e.stopPropagation();
        showBuildingPopup(b, e);
      });
      hit.addEventListener("touchend", (e) => {
        e.stopPropagation();
        showBuildingPopup(b, e.changedTouches[0]);
      });
      overlayEl.appendChild(hit);
    });

    overlayEl.addEventListener("click", hidePopup);

    buildCompass();
    startCompass();
    buildZoomControls();
    buildLocateButton();
    buildSearchBar();
    buildLocatingToast();

    popupEl = document.createElement("div");
    popupEl.id = "mapPopup";
    popupEl.style.cssText = `
      display:none; position:absolute; z-index:100;
      pointer-events:all;
    `;
    mapWrapEl.appendChild(popupEl);

    fitMap();
    bindPanZoom();
    startGPS();

    if (destBuildingId) drawRoute();
  }

  function buildZoomControls() {
    const wrap = document.createElement("div");
    wrap.id = "mapZoomControls";
    wrap.innerHTML = `
      <button type="button" class="zoom-btn zoom-in" aria-label="Zoom in"><i class="fas fa-plus"></i></button>
      <div class="zoom-divider"></div>
      <button type="button" class="zoom-btn zoom-out" aria-label="Zoom out"><i class="fas fa-minus"></i></button>
    `;
    mapWrapEl.appendChild(wrap);
    wrap.querySelector(".zoom-in").addEventListener("click", (e) => {
      e.stopPropagation();
      const cw = mapWrapEl.clientWidth,
        ch = mapWrapEl.clientHeight;
      zoomAt(cw / 2, ch / 2, 1.35);
    });
    wrap.querySelector(".zoom-out").addEventListener("click", (e) => {
      e.stopPropagation();
      const cw = mapWrapEl.clientWidth,
        ch = mapWrapEl.clientHeight;
      zoomAt(cw / 2, ch / 2, 0.74);
    });
  }

  function buildLocateButton() {
    locateBtnEl = document.createElement("button");
    locateBtnEl.type = "button";
    locateBtnEl.id = "mapLocateBtn";
    locateBtnEl.setAttribute("aria-label", "Jump to my location");
    locateBtnEl.innerHTML = `<i class="fas fa-location-crosshairs"></i>`;
    locateBtnEl.addEventListener("click", (e) => {
      e.stopPropagation();
      autoCenterOnLocation = false;
      jumpToUser();
    });
    mapWrapEl.appendChild(locateBtnEl);
  }

  function jumpToUser() {
    if (!currentUserNode) {
      showLocatingToast("Finding your location…");
      return;
    }
    locateBtnEl.classList.add("locating");
    const n = NODE_MAP[currentUserNode];
    smoothAngle = 0;
    if (viewScale < MIN_SCALE * 1.3) {
      const cw = mapWrapEl.clientWidth,
        ch = mapWrapEl.clientHeight;
      zoomAt(cw / 2, ch / 2, (MIN_SCALE * 1.6) / viewScale);
    }
    snapToUser(n.x, n.y);
    setTimeout(() => locateBtnEl.classList.remove("locating"), 500);
    showYouAreHerePopup({
      clientX:
        mapWrapEl.getBoundingClientRect().left + mapWrapEl.clientWidth / 2,
      clientY:
        mapWrapEl.getBoundingClientRect().top + mapWrapEl.clientHeight * 0.55,
    });
  }

  function buildLocatingToast() {
    locatingToastEl = document.createElement("div");
    locatingToastEl.id = "mapLocatingToast";
    locatingToastEl.style.display = "none";
    mapWrapEl.appendChild(locatingToastEl);
  }

  let toastTimer = null;
  function showLocatingToast(msg) {
    if (!locatingToastEl) return;
    locatingToastEl.textContent = msg;
    locatingToastEl.style.display = "block";
    requestAnimationFrame(() => locatingToastEl.classList.add("show"));
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      locatingToastEl.classList.remove("show");
      setTimeout(() => {
        locatingToastEl.style.display = "none";
      }, 250);
    }, 2200);
  }

  function buildSearchBar() {
    const headerBar = document.getElementById("mapHeaderBar");
    if (!headerBar) return;

    searchInputEl = document.getElementById("mapSearchInput");
    searchResultsEl = document.getElementById("mapSearchResults");
    const clearBtn = document.getElementById("mapSearchClear");
    if (!searchInputEl || !searchResultsEl || !clearBtn) return;

    searchInputEl.addEventListener("focus", () => {
      headerBar.classList.add("focused");
      renderSearchResults(searchInputEl.value);
    });
    searchInputEl.addEventListener("input", () => {
      clearBtn.style.display = searchInputEl.value ? "flex" : "none";
      renderSearchResults(searchInputEl.value);
    });
    clearBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      searchInputEl.value = "";
      clearBtn.style.display = "none";
      renderSearchResults("");
      searchInputEl.focus();
    });
    document.addEventListener("click", (e) => {
      if (!headerBar.contains(e.target)) {
        headerBar.classList.remove("focused");
        searchResultsEl.style.display = "none";
      }
    });
    headerBar.addEventListener("click", (e) => e.stopPropagation());
  }

  function renderSearchResults(query) {
    const q = (query || "").trim().toLowerCase();
    let matches = BUILDINGS_INFO;
    if (q) {
      matches = BUILDINGS_INFO.filter((b) => {
        const hay = (
          b.name +
          " " +
          b.short +
          " " +
          b.category +
          " " +
          b.offices.join(" ")
        ).toLowerCase();
        return hay.includes(q);
      });
    }
    if (!matches.length) {
      searchResultsEl.innerHTML = `<div class="search-empty">No matches for "${escapeHtml(
        query,
      )}"</div>`;
      searchResultsEl.style.display = "block";
      return;
    }
    searchResultsEl.innerHTML = matches
      .map(
        (b) => `
      <button type="button" class="search-result" data-id="${b.id}">
        <span class="sr-dot" style="background:${b.color}"></span>
        <span class="sr-text">
          <span class="sr-name">${escapeHtml(b.short)}</span>
        </span>
        <i class="fas fa-chevron-right sr-chev"></i>
      </button>`,
      )
      .join("");
    searchResultsEl.style.display = "block";
    searchResultsEl.querySelectorAll(".search-result").forEach((btn) => {
      btn.addEventListener("click", () => {
        const id = btn.getAttribute("data-id");
        const b = BUILDINGS_INFO.find((x) => x.id === id);
        if (!b) return;
        goToBuilding(b);
        searchResultsEl.style.display = "none";
        searchInputEl.blur();
      });
    });
  }

  function escapeHtml(s) {
    return String(s || "").replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[c],
    );
  }

  function goToBuilding(b) {
    const cw = mapWrapEl.clientWidth,
      ch = mapWrapEl.clientHeight;
    if (viewScale < MIN_SCALE * 1.5) {
      zoomAt(cw / 2, ch / 2, (MIN_SCALE * 1.8) / viewScale);
    }
    smoothAngle = 0;
    viewAngle = 0;
    const clipX = b.cx - CAMPUS.x;
    const clipY = b.cy - CAMPUS.y;
    viewX = cw / 2 - clipX * viewScale;
    viewY = ch * 0.5 - clipY * viewScale;
    clampPan();
    applyTransform();
    showBuildingPopup(b, {
      clientX: mapWrapEl.getBoundingClientRect().left + cw / 2,
      clientY: mapWrapEl.getBoundingClientRect().top + ch * 0.4,
    });
  }

  let nodeDebugGroupEl = null;
  let nodeDebugVisible = false;

  function buildNodeDebugLayer() {
    nodeDebugGroupEl = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "g",
    );
    nodeDebugGroupEl.id = "nodeDebugGroup";
    nodeDebugGroupEl.style.display = "none";

    NODES.forEach((n) => {
      const isGate = n.id.startsWith("gate");
      const isBuilding = n.id.startsWith("b_");
      const color = isGate ? "#e53935" : isBuilding ? "#1a73e8" : "#ff9800";
      const r = isGate || isBuilding ? 14 : 9;

      const g = document.createElementNS("http://www.w3.org/2000/svg", "g");
      g.setAttribute("transform", `translate(${n.x},${n.y})`);

      const c = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "circle",
      );
      c.setAttribute("r", r);
      c.setAttribute("fill", color);
      c.setAttribute("stroke", "#ffffff");
      c.setAttribute("stroke-width", "2");
      g.appendChild(c);

      const t = document.createElementNS("http://www.w3.org/2000/svg", "text");
      t.setAttribute("y", -(r + 6));
      t.setAttribute("text-anchor", "middle");
      t.setAttribute("font-size", "16");
      t.setAttribute("font-weight", "700");
      t.setAttribute("font-family", "'DM Mono', monospace");
      t.setAttribute("fill", "#111");
      t.setAttribute("stroke", "#fff");
      t.setAttribute("stroke-width", "3");
      t.setAttribute("paint-order", "stroke");
      t.textContent = n.id;
      g.appendChild(t);

      nodeDebugGroupEl.appendChild(g);
    });

    const edgeLayer = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "g",
    );
    EDGES.forEach(([a, b]) => {
      const na = NODE_MAP[a],
        nb = NODE_MAP[b];
      if (!na || !nb) return;
      const line = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "line",
      );
      line.setAttribute("x1", na.x);
      line.setAttribute("y1", na.y);
      line.setAttribute("x2", nb.x);
      line.setAttribute("y2", nb.y);
      line.setAttribute("stroke", "#999");
      line.setAttribute("stroke-width", "3");
      line.setAttribute("opacity", "0.6");
      edgeLayer.appendChild(line);
    });
    nodeDebugGroupEl.insertBefore(edgeLayer, nodeDebugGroupEl.firstChild);

    overlayEl.appendChild(nodeDebugGroupEl);

    const btn = document.createElement("button");
    btn.type = "button";
    btn.id = "mapNodeDebugBtn";
    btn.textContent = "Nodes";
    btn.style.cssText = `
      position:absolute; top:14px; right:62px; z-index:41;
      background:#111; color:#fff; font-size:12px; font-weight:700;
      padding:8px 12px; border-radius:10px; border:none; cursor:pointer;
      box-shadow:0 3px 14px rgba(0,40,20,0.18);
    `;
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      toggleNodeDebug();
      btn.style.background = nodeDebugVisible ? "#0a6635" : "#111";
    });
    mapWrapEl.appendChild(btn);

    toggleNodeDebug(true);
    btn.style.background = "#0a6635";
  }

  function toggleNodeDebug(force) {
    if (!nodeDebugGroupEl) return;
    nodeDebugVisible = force !== undefined ? force : !nodeDebugVisible;
    nodeDebugGroupEl.style.display = nodeDebugVisible ? "block" : "none";
  }

  function buildCompass() {
    compassEl = document.createElement("div");
    compassEl.id = "mapCompass";
    compassEl.innerHTML = `
      <div class="compass-needle">
        <svg width="18" height="18" viewBox="0 0 18 18">
          <polygon points="9,1 12,9 9,7 6,9" fill="#e53935"/>
          <polygon points="9,17 12,9 9,11 6,9" fill="#9e9e9e"/>
        </svg>
      </div>
    `;
    compassEl.addEventListener("click", () => {
      smoothAngle = 0;
      viewAngle = 0;
      applyTransform();
    });
    mapWrapEl.appendChild(compassEl);
  }

  function stopOrientationWatchers() {
    window.removeEventListener("deviceorientationabsolute", onOrientation, true);
    window.removeEventListener("deviceorientation", onOrientation, true);
  }

  function startCompass() {
    disableDeviceOrientation = true;
    stopOrientationWatchers();
  }

  function listenOrientation() {
    if (disableDeviceOrientation) return;
    window.addEventListener("deviceorientationabsolute", onOrientation, true);
    window.addEventListener("deviceorientation", onOrientation, true);
  }

  let compassDeg = 0;

  function onOrientation(e) {
    if (disableDeviceOrientation) return;
    let heading = null;
    if (e.webkitCompassHeading !== undefined) {
      heading = e.webkitCompassHeading;
    } else if (e.absolute && e.alpha !== null) {
      heading = (360 - e.alpha) % 360;
    }
    if (heading === null) return;
    compassDeg = heading;
    deviceHeadingActive = true;
    if (userDot) {
      const headingG = userDot.querySelector(".dot-heading");
      if (headingG) headingG.setAttribute("opacity", "1");
    }
    applyHeading(heading, false);
  }

  function applyHeading(headingDeg, snap) {
    if (disableDeviceOrientation) return;
    if (headingDeg === null || headingDeg === undefined || isNaN(headingDeg))
      return;
    const targetAngle = -((headingDeg * Math.PI) / 180);
    if (snap) {
      smoothAngle = targetAngle;
    } else {
      let diff = targetAngle - smoothAngle;
      while (diff > Math.PI) diff -= 2 * Math.PI;
      while (diff < -Math.PI) diff += 2 * Math.PI;
      smoothAngle += HEADING_ALPHA * diff;
    }
    viewAngle = smoothAngle;
    enforceMinZoom();
    clampPan();
    applyTransform();
  }

  function renderCompassNeedle() {
    if (!compassEl) return;
    const needle = compassEl.querySelector(".compass-needle");
    if (!needle) return;
    const deg = compassDeg + (viewAngle * 180) / Math.PI;
    needle.style.transform = `rotate(${deg}deg)`;
  }

  function fitMap() {
    if (!mapWrapEl) return;
    const cw = mapWrapEl.clientWidth;
    const ch = mapWrapEl.clientHeight;
    const fitScale = Math.max(cw / CAMPUS.w, ch / CAMPUS.h);
    MIN_SCALE = fitScale * 2;
    viewScale = fitScale * 2;
    viewX = (cw - CAMPUS.w * viewScale) / 2;
    viewY = (ch - CAMPUS.h * viewScale) / 2;
    applyTransform();
  }

  function bindPanZoom() {
    const el = mapWrapEl;
    let lastTouchDist = null;
    let lastPinchScale = 1;
    let lastTwistAngle = null;
    let gestureMode = null;

    el.addEventListener(
      "wheel",
      (e) => {
        e.preventDefault();
        const factor = e.deltaY < 0 ? 1.15 : 0.87;
        const rect = el.getBoundingClientRect();
        zoomAt(e.clientX - rect.left, e.clientY - rect.top, factor);
      },
      { passive: false },
    );

    el.addEventListener(
      "touchstart",
      (e) => {
        if (e.touches.length === 1) {
          gestureMode = "pan";
          isPanning = true;
          autoCenterOnLocation = false;
          panStartX = e.touches[0].clientX;
          panStartY = e.touches[0].clientY;
          panOriginX = viewX;
          panOriginY = viewY;
        } else if (e.touches.length === 2) {
          gestureMode = "pinch";
          isPanning = false;
          lastTouchDist = getTouchDist(e.touches);
          lastTwistAngle = getTouchAngle(e.touches);
          lastPinchScale = viewScale;
        }
      },
      { passive: true },
    );

    el.addEventListener(
      "touchmove",
      (e) => {
        e.preventDefault();
        if (e.touches.length === 1 && gestureMode === "pan" && isPanning) {
          const dx = e.touches[0].clientX - panStartX;
          const dy = e.touches[0].clientY - panStartY;
          const cos = Math.cos(-viewAngle);
          const sin = Math.sin(-viewAngle);
          viewX = panOriginX + (dx * cos - dy * sin);
          viewY = panOriginY + (dx * sin + dy * cos);
          clampPan();
          applyTransform();
        } else if (e.touches.length === 2 && gestureMode === "pinch") {
          const d = getTouchDist(e.touches);
          const ang = getTouchAngle(e.touches);
          const rect = mapWrapEl.getBoundingClientRect();
          const cx =
            (e.touches[0].clientX + e.touches[1].clientX) / 2 - rect.left;
          const cy =
            (e.touches[0].clientY + e.touches[1].clientY) / 2 - rect.top;

          const factor = d / lastTouchDist / (viewScale / lastPinchScale);
          zoomAt(cx, cy, factor);

          if (lastTwistAngle !== null) {
            const delta = ang - lastTwistAngle;
            viewAngle += delta;
            enforceMinZoom();
            clampPan();
            scheduleTransform();
          }

          lastTouchDist = d;
          lastTwistAngle = ang;
          lastPinchScale = viewScale;
        }
      },
      { passive: false },
    );

    el.addEventListener("touchend", (e) => {
      if (e.touches.length === 0) {
        isPanning = false;
        gestureMode = null;
        lastTouchDist = null;
        lastTwistAngle = null;
      } else if (e.touches.length === 1) {
        gestureMode = "pan";
        isPanning = true;
        panStartX = e.touches[0].clientX;
        panStartY = e.touches[0].clientY;
        panOriginX = viewX;
        panOriginY = viewY;
        lastTouchDist = null;
        lastTwistAngle = null;
      }
    });

    el.addEventListener("mousedown", (e) => {
      if (
        !(e.target && e.target.closest && e.target.closest("#mapSearchInput"))
      ) {
        e.preventDefault();
      }
      if (e.button === 2) {
        isRotating = true;
        autoCenterOnLocation = false;
        rotateStartX = e.clientX;
        rotateStartAngle = viewAngle;
      } else {
        isPanning = true;
        autoCenterOnLocation = false;
        panStartX = e.clientX;
        panStartY = e.clientY;
        panOriginX = viewX;
        panOriginY = viewY;
        el.style.cursor = "grabbing";
      }
    });
    el.addEventListener("mousemove", (e) => {
      if (isRotating) {
        viewAngle = rotateStartAngle + (e.clientX - rotateStartX) * 0.005;
        enforceMinZoom();
        clampPan();
        scheduleTransform();
        return;
      }
      if (!isPanning) return;

      const dx = e.clientX - panStartX;
      const dy = e.clientY - panStartY;
      const cos = Math.cos(-viewAngle);
      const sin = Math.sin(-viewAngle);
      viewX = panOriginX + (dx * cos - dy * sin);
      viewY = panOriginY + (dx * sin + dy * cos);
      clampPan();
      scheduleTransform();
    });
    window.addEventListener("mouseup", () => {
      isPanning = false;
      isRotating = false;
      el.style.cursor = "grab";
    });
    el.addEventListener("contextmenu", (e) => e.preventDefault());
    document.addEventListener("keydown", (e) => {
      if (e.key === "q") {
        viewAngle -= 0.05;
        enforceMinZoom();
        clampPan();
        applyTransform();
      }
      if (e.key === "e") {
        viewAngle += 0.05;
        enforceMinZoom();
        clampPan();
        applyTransform();
      }
    });
  }

  function getTouchDist(touches) {
    return Math.hypot(
      touches[0].clientX - touches[1].clientX,
      touches[0].clientY - touches[1].clientY,
    );
  }

  function getTouchAngle(touches) {
    return Math.atan2(
      touches[1].clientY - touches[0].clientY,
      touches[1].clientX - touches[0].clientX,
    );
  }

  function enforceMinZoom() {
    const cw = mapWrapEl.clientWidth;
    const ch = mapWrapEl.clientHeight;
    const a = Math.abs(viewAngle % (Math.PI / 2));
    const na = a > Math.PI / 4 ? Math.PI / 2 - a : a;
    const diagFactor =
      Math.cos(na) +
      (Math.sin(na) * Math.min(CAMPUS.w, CAMPUS.h)) /
        Math.max(CAMPUS.w, CAMPUS.h);
    const minForAngle = MIN_SCALE / diagFactor;
    if (viewScale < minForAngle) {
      viewScale = minForAngle;
    }
  }

  function zoomAt(cx, cy, factor) {
    const MAX_SCALE = MIN_SCALE * 4;
    const newScale = Math.min(
      Math.max(viewScale * factor, MIN_SCALE),
      MAX_SCALE,
    );
    const ratio = newScale / viewScale;
    viewX = cx - ratio * (cx - viewX);
    viewY = cy - ratio * (cy - viewY);
    viewScale = newScale;
    enforceMinZoom();
    clampPan();
    applyTransform();
    updateZoomBtnState();
  }

  function updateZoomBtnState() {
    const wrap = document.getElementById("mapZoomControls");
    if (!wrap) return;
    const MAX_SCALE = MIN_SCALE * 4;
    wrap.querySelector(".zoom-in").disabled = viewScale >= MAX_SCALE - 1e-6;
    wrap.querySelector(".zoom-out").disabled = viewScale <= MIN_SCALE + 1e-6;
  }


  function showBuildingPopup(b, e) {
    if (!popupEl || !mapWrapEl) return;
    const rect = mapWrapEl.getBoundingClientRect();
    let px = (e.clientX ?? e.pageX) - rect.left;
    let py = (e.clientY ?? e.pageY) - rect.top;

    const pinX = b.cx;
    const pinY = b.cy - b.ry - 34;
    activePopupSVG = { x: pinX, y: pinY };

    const officesHtml = b.offices.length
      ? `<div class="popup-offices">
           ${b.offices.map((o) => `<div class="popup-office-row"><i class="fas fa-circle-dot"></i>${escapeHtml(o)}</div>`).join("")}
         </div>`
      : "";

    const effectiveDestParent =
      DEST_PARENT_BUILDING[destBuildingId] || destBuildingId;
    const isTicketDest = effectiveDestParent === b.id;
    const destBadge = isTicketDest
      ? `<div class="popup-dest-badge">
           <i class="fas fa-ticket"></i><span>Your destination — follow the yellow path</span>
         </div>`
      : "";

    popupEl.innerHTML = `
      <div class="popup-card">
        <div class="popup-head" style="background:${b.color}">
          <div class="popup-head-short">${escapeHtml(b.short)}</div>
          <button type="button" class="popup-close" aria-label="Close"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="popup-body">
          <div class="popup-title">${escapeHtml(b.name)}</div>
          ${officesHtml}
          ${destBadge}
        </div>
      </div>
    `;

    popupEl.style.display = "block";
    popupEl.querySelector(".popup-close").addEventListener("click", (ev) => {
      ev.stopPropagation();
      hidePopup();
    });

    positionPopup(px, py);
  }

  function positionPopup(px, py) {
    const pw = popupEl.offsetWidth || 260;
    const ph = popupEl.offsetHeight || 180;
    const cw = mapWrapEl.clientWidth;
    const ch = mapWrapEl.clientHeight;
    const margin = 8;

    let left = Math.min(Math.max(px - pw / 2, margin), cw - pw - margin);
    let top = py - ph - 24;
    if (top < margin) top = py + 24;
    top = Math.min(Math.max(top, margin), ch - ph - margin);

    popupEl.style.left = left + "px";
    popupEl.style.top = top + "px";
  }

  function hidePopup() {
    if (popupEl) popupEl.style.display = "none";
    activePopupSVG = null;
  }

  function showYouAreHerePopup(e) {
    if (!popupEl || !mapWrapEl) return;
    const rect = mapWrapEl.getBoundingClientRect();
    let px = (e.clientX ?? e.pageX) - rect.left;
    let py = (e.clientY ?? e.pageY) - rect.top;

    if (userSvgX !== null && userSvgY !== null) {
      activePopupSVG = { x: userSvgX, y: userSvgY };
    }

    popupEl.innerHTML = `
      <div class="popup-card youarehere-card">
        <div class="popup-body" style="padding-top:16px">
          <div style="display:flex;align-items:center;gap:10px">
            <i class="fas fa-location-dot" style="color:#1a73e8;font-size:16px"></i>
            <div class="popup-title" style="margin:0">You are here</div>
          </div>
        </div>
      </div>
    `;

    popupEl.style.display = "block";
    positionPopup(px, py);
  }

  function startGPS() {
    if (!navigator.geolocation) {
      showGPSError(
        "Location isn't supported on this device — tap a building to see directions.",
      );
      return;
    }
    showLocatingToast("Finding your location…");
    watchId = navigator.geolocation.watchPosition(onPosition, onGeoError, {
      enableHighAccuracy: true,
      maximumAge: 3000,
      timeout: 10000,
    });
  }

  function onGeoError(err) {
    let msg = "Location unavailable — tap a building to see directions.";
    if (err && err.code === err.PERMISSION_DENIED) {
      msg =
        "Location permission denied — enable it in your browser settings, or tap a building to see directions.";
    } else if (err && err.code === err.TIMEOUT) {
      msg = "Couldn't get your location — retrying…";
    }
    showGPSError(msg);
  }

  function showGPSError(msg) {
    if (!mapWrapEl) return;
    const bar = document.getElementById("mapDirectionsBar");
    const txt = document.getElementById("mapDirectionsText");
    if (bar && txt && !gpsErrorShown) {
      gpsErrorShown = true;
      txt.textContent = msg;
      bar.style.display = "flex";
    }
  }

  function onPosition(pos) {
    if (gpsErrorShown) {
      gpsErrorShown = false;
      if (!destBuildingId) {
        const bar = document.getElementById("mapDirectionsBar");
        if (bar) bar.style.display = "none";
      }
    }

    const { latitude: lat, longitude: lng } = pos.coords;
    const svgPos = gpsToSvg(lat, lng);
    const snapped = nearestNode(svgPos.x, svgPos.y, currentUserNode);
    currentUserNode = snapped.id;
    moveUserDot(snapped.x, snapped.y);
    updateWalkChip(snapped);
    if (destBuildingId) drawRoute();

    const isFirstFix = firstFix;

    if (isFirstFix) {
      firstFix = false;
      autoCenterOnLocation = false;
      snapToUser(snapped.x, snapped.y);
      if (locatingToastEl) {
        locatingToastEl.classList.remove("show");
      }
    } else if (autoCenterOnLocation) {
      centerOnUser(snapped.x, snapped.y);
    }
  }

  function moveUserDot(svgX, svgY) {
    userSvgX = svgX;
    userSvgY = svgY;
    userDot.setAttribute("transform", `translate(${svgX},${svgY})`);
    userDot.setAttribute("opacity", "1");

    if (activePopupSVG && popupEl && popupEl.style.display !== "none") {
      activePopupSVG = { x: svgX, y: svgY };
    }
  }

  function centerOnUser(svgX, svgY) {
    if (!mapIsVisible()) return;
    const cw = mapWrapEl.clientWidth;
    const ch = mapWrapEl.clientHeight;
    const clipX = svgX - CAMPUS.x;
    const clipY = svgY - CAMPUS.y;
    const targetX = cw / 2 - clipX * viewScale;
    const targetY = ch * 0.55 - clipY * viewScale;
    viewX = viewX * 0.75 + targetX * 0.25;
    viewY = viewY * 0.75 + targetY * 0.25;
    clampPan();
    applyTransform();
  }

  function snapToUser(svgX, svgY) {
    if (!mapIsVisible()) return;
    const cw = mapWrapEl.clientWidth;
    const ch = mapWrapEl.clientHeight;
    const clipX = svgX - CAMPUS.x;
    const clipY = svgY - CAMPUS.y;
    viewX = cw / 2 - clipX * viewScale;
    viewY = ch * 0.55 - clipY * viewScale;
    clampPan();
    applyTransform();
  }

  function drawRoute() {
    if (!destBuildingId) return;
    const destNodeId = BUILDING_NODE[destBuildingId];
    if (!destNodeId) return;

    const startId = currentUserNode || "gate1";
    const pathIds = aStar(startId, destNodeId);

    if (!pathIds) {
      pathLine.setAttribute("opacity", "0");
      const casing = document.getElementById("pathCasing");
      if (casing) casing.setAttribute("opacity", "0");
      return;
    }

    const pts = pathIds
      .map((id) => {
        const n = NODE_MAP[id];
        return `${n.x},${n.y}`;
      })
      .join(" ");
    pathLine.setAttribute("points", pts);
    pathLine.setAttribute("opacity", "1");
    const casing = document.getElementById("pathCasing");
    if (casing) {
      casing.setAttribute("points", pts);
      casing.setAttribute("opacity", "1");
    }

    const destNode = NODE_MAP[destNodeId];
    destMarker.setAttribute(
      "transform",
      `translate(${destNode.x},${destNode.y})`,
    );
    destMarker.setAttribute("opacity", "1");

    updateHeader();
    updateDirectionsBar(pathIds);
  }

  function updateHeader() {
    const bInfo = BUILDING_INFO[destBuildingId];
    const destEl = document.getElementById("mapDestName");
    if (destEl && bInfo) destEl.textContent = bInfo.short;
  }

  function updateWalkChip(userNode) {
    if (!destBuildingId) return;
    const destNodeId = BUILDING_NODE[destBuildingId];
    if (!destNodeId) return;
    const pathIds = aStar(userNode.id, destNodeId);
    if (!pathIds) return;

    let totalPx = 0;
    for (let i = 0; i < pathIds.length - 1; i++) {
      totalPx += dist(NODE_MAP[pathIds[i]], NODE_MAP[pathIds[i + 1]]);
    }
    const meters = totalPx * 0.045;
    const mins = Math.max(1, Math.round(meters / 1.2 / 60));

    const chip = document.getElementById("mapWalkChip");
    const chipTime = document.getElementById("mapWalkTime");
    if (chip && chipTime) {
      chipTime.textContent = `~${mins} min`;
      chip.style.display = "flex";
    }
  }

  function updateDirectionsBar(pathIds) {
    if (gpsErrorShown) return;
    const bar = document.getElementById("mapDirectionsBar");
    const txt = document.getElementById("mapDirectionsText");
    if (!bar || !txt) return;
    const bInfo = BUILDING_INFO[destBuildingId];
    txt.textContent = `Head towards ${bInfo ? bInfo.name : "your destination"} — follow the yellow path`;
    bar.style.display = "flex";
  }

  window.CampusMap = {
    init: initMap,
    refit: fitMap,
    toggleNodes: toggleNodeDebug,

    setDestination(officeName, buildingHint) {
      const id = resolveBuildingId(officeName, buildingHint);
      if (!id) return;
      destBuildingId = id;
      updateHeader();
      if (mapInitialized) {
        drawRoute();
        updateWalkChip(
          currentUserNode ? NODE_MAP[currentUserNode] : NODE_MAP["gate1"],
        );
      }
    },
  };
})();
