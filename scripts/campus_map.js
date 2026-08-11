(function () {
  "use strict";

  const CALIBRATION_POINTS = [
    { lat: 14.620303859819074, lng: 121.15358155696558, x: 3300, y: 639 },
    { lat: 14.62001165707186, lng: 121.15312420991697, x: 2687, y: 784 },
    { lat: 14.61943626769976, lng: 121.15247410852331, x: 1735, y: 760 },
    { lat: 14.61959129503177, lng: 121.15203968057038, x: 1168, y: 654 },
    { lat: 14.619564463385176, lng: 121.15166379256078, x: 610, y: 443 },
    { lat: 14.619263352459713, lng: 121.1516791978071, x: 608, y: 791 },
    { lat: 14.61928295764446, lng: 121.15189987667863, x: 830, y: 879 },
    { lat: 14.619672262909887, lng: 121.15284669652685, x: 2225, y: 850 },
    { lat: 14.619918822564339, lng: 121.153348269658, x: 2980, y: 980 },
    { lat: 14.620471634244192, lng: 121.1539061691739, x: 3700, y: 596 },
  ];

  function solve3x3(M, b) {
    const det = (m) =>
      m[0][0] * (m[1][1] * m[2][2] - m[1][2] * m[2][1]) -
      m[0][1] * (m[1][0] * m[2][2] - m[1][2] * m[2][0]) +
      m[0][2] * (m[1][0] * m[2][1] - m[1][1] * m[2][0]);
    const D = det(M);
    const Mx = [
      [b[0], M[0][1], M[0][2]],
      [b[1], M[1][1], M[1][2]],
      [b[2], M[2][1], M[2][2]],
    ];
    const My = [
      [M[0][0], b[0], M[0][2]],
      [M[1][0], b[1], M[1][2]],
      [M[2][0], b[2], M[2][2]],
    ];
    const Mz = [
      [M[0][0], M[0][1], b[0]],
      [M[1][0], M[1][1], b[1]],
      [M[2][0], M[2][1], b[2]],
    ];
    return [det(Mx) / D, det(My) / D, det(Mz) / D];
  }

  function fitAffine(pts) {
    let Sxx = 0,
      Sxy = 0,
      Sx = 0,
      Syy = 0,
      Sy = 0,
      S = 0,
      SxX = 0,
      SyX = 0,
      SX = 0,
      SxY = 0,
      SyY = 0,
      SY = 0;
    for (const p of pts) {
      const { lng, lat, x, y } = p;
      Sxx += lng * lng;
      Sxy += lng * lat;
      Sx += lng;
      Syy += lat * lat;
      Sy += lat;
      S += 1;
      SxX += lng * x;
      SyX += lat * x;
      SX += x;
      SxY += lng * y;
      SyY += lat * y;
      SY += y;
    }
    const M = [
      [Sxx, Sxy, Sx],
      [Sxy, Syy, Sy],
      [Sx, Sy, S],
    ];
    const [A, B, C] = solve3x3(M, [SxX, SyX, SX]);
    const [D, E, F] = solve3x3(M, [SxY, SyY, SY]);
    return { A, B, C, D, E, F };
  }

  const T = fitAffine(CALIBRATION_POINTS);
  const CALIB_NEIGHBOR_LIMIT = 4;
  const CAMPUS_GATE_POINTS = [
    { id: "gate1", lat: 14.619263352459713, lng: 121.1516791978071 },
    { id: "gate2", lat: 14.61928295764446, lng: 121.15189987667863 },
    { id: "gate3", lat: 14.619672262909887, lng: 121.15284669652685 },
    { id: "gate4", lat: 14.619918822564339, lng: 121.153348269658 },
    { id: "gate5", lat: 14.620471634244192, lng: 121.1539061691739 },
  ];

  const CALIB_RESIDUALS = CALIBRATION_POINTS.map((p) => {
    const bx = T.A * p.lng + T.B * p.lat + T.C;
    const by = T.D * p.lng + T.E * p.lat + T.F;
    return { lat: p.lat, lng: p.lng, rx: p.x - bx, ry: p.y - by };
  });

  function haversineMeters(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const toRad = (deg) => (deg * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(toRad(lat1)) *
        Math.cos(toRad(lat2)) *
        Math.sin(dLng / 2) *
        Math.sin(dLng / 2);
    return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  function nearestCampusGate(lat, lng) {
    const candidates = CAMPUS_GATE_POINTS.map((gate) => ({
      ...gate,
      distance: haversineMeters(lat, lng, gate.lat, gate.lng),
    }));
    return candidates.sort((a, b) => a.distance - b.distance)[0];
  }

  function gpsToSvg(lat, lng) {
    const bx = T.A * lng + T.B * lat + T.C;
    const by = T.D * lng + T.E * lat + T.F;

    const nearby = CALIB_RESIDUALS.map((r) => {
      const dlat = lat - r.lat,
        dlng = lng - r.lng;
      const d2 = dlat * dlat + dlng * dlng;
      return { r, d2 };
    })
      .filter(({ d2 }) => d2 > 0)
      .sort((a, b) => a.d2 - b.d2)
      .slice(0, CALIB_NEIGHBOR_LIMIT);

    if (!nearby.length) {
      return { x: bx, y: by };
    }

    let wSum = 0,
      cx = 0,
      cy = 0;
    for (const { r, d2 } of nearby) {
      if (d2 < 1e-18) return { x: bx + r.rx, y: by + r.ry };
      const w = 1 / d2;
      wSum += w;
      cx += w * r.rx;
      cy += w * r.ry;
    }
    return { x: bx + cx / wSum, y: by + cy / wSum };
  }

  const NODES = [
    { id: "gate1", x: 608, y: 791 },
    { id: "gate2", x: 830, y: 879 },
    { id: "gate3", x: 2225, y: 850 },
    { id: "gate4", x: 2980, y: 980 },
    { id: "gate5", x: 3700, y: 596 },
    { id: "j_1", x: 700, y: 693 },
    { id: "j_2", x: 800, y: 550 },
    { id: "j_41", x: 875, y: 465 },
    { id: "j_3", x: 730, y: 493 },
    { id: "j_4", x: 950, y: 380 },
    { id: "j_5", x: 980, y: 330 },
    { id: "j_6", x: 1032, y: 328 },
    { id: "j_38", x: 1196, y: 374 },
    { id: "j_7", x: 1360, y: 420 },
    { id: "j_8", x: 1330, y: 470 },
    { id: "j_9", x: 1500, y: 460 },
    { id: "j_10", x: 1270, y: 750 },
    { id: "j_39", x: 1385, y: 605 },
    { id: "j_40", x: 1503, y: 755 },
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
    { id: "j_42", x: 1923, y: 669 },
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
    ["j_2", "j_41"],
    ["j_41", "j_4"],
    ["j_4", "j_5"],
    ["j_5", "j_6"],
    ["j_6", "j_38"],
    ["j_38", "j_7"],
    ["j_7", "j_8"],
    ["j_8", "b_jsb"],
    ["b_jsb", "2a"],
    ["j_7", "j_9"],
    ["j_9", "j_16"],
    ["j_9", "j_39"],
    ["j_39", "j_10"],
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
    ["j_20", "j_42"],
    ["j_42", "j_21"],
    ["j_20", "b_canteen"],
    ["j_10", "j_40"],
    ["j_40", "b_sjbh"],
    ["j_20", "b_sjbh"],
    ["b_sjbh", "3a"],
    ["j_21", "j_24"],
    ["j_22", "b_sjbh"],
    ["j_22", "j_24"],
    ["gate3", "j_24"],
    ["j_24", "j_25"],
    ["j_25", "b_sch"],
    ["j_25", "j_26"],
    ["j_26", "j_28"],
    ["j_26", "b_sch"],
    ["gate4", "j_26"],
    ["j_28", "j_27"],
    ["j_27", "j_29"],
    ["j_27", "b_spcb"],
    ["j_29", "b_spcb"],
    ["j_29", "gate5"],
    ["j_28", "j_27"],
    ["j_28", "j_26"],
    ["j_29", "j_27"],
    ["j_29", "b_spcb"],
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
    {
      id: "FUMC",
      label: "FUMC",
      x: 1550,
      y: 1090,
      lines: ["Fatima University", "Medical Center"],
    },
    {
      id: "AVM",
      label: "AVM",
      x: 1550,
      y: 1590,
      lines: ["Antipolo Valley", "Mall"],
    },
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

  function pointToSegment(px, py, ax, ay, bx, by) {
    const dx = bx - ax,
      dy = by - ay;
    const lenSq = dx * dx + dy * dy;
    if (lenSq < 1e-12) {
      const cx = (ax + bx) / 2,
        cy = (ay + by) / 2;
      return { t: 0.5, distSq: (px - cx) ** 2 + (py - cy) ** 2, cx, cy };
    }
    let t = ((px - ax) * dx + (py - ay) * dy) / lenSq;
    t = Math.max(0, Math.min(1, t));
    const cx = ax + t * dx,
      cy = ay + t * dy;
    return { t, distSq: (px - cx) ** 2 + (py - cy) ** 2, cx, cy };
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
  const GPS_ACCEPTABLE_ACCURACY = 15;
  const GPS_MAX_ACCEPTABLE_ACCURACY = 60;

  function nearestNode(svgX, svgY, preferId, accuracyMeters = null) {
    let best = null,
      bestD = Infinity;
    NODES.forEach((n) => {
      const d = dist({ x: svgX, y: svgY }, n);
      if (d < bestD) {
        bestD = d;
        best = n;
      }
    });

    if (!best) return null;

    const maxSnapDistance = accuracyMeters
      ? Math.min(360, Math.max(120, accuracyMeters * 5))
      : 240;

    if (bestD > maxSnapDistance) return null;

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

  function snapToNearestEdge(svgX, svgY) {
    let best = { a: null, b: null, t: 0, distSq: Infinity, x: svgX, y: svgY };

    for (let i = 0; i < EDGES.length; i++) {
      const [aId, bId] = EDGES[i];
      const na = NODE_MAP[aId],
        nb = NODE_MAP[bId];
      if (!na || !nb) continue;
      const r = pointToSegment(svgX, svgY, na.x, na.y, nb.x, nb.y);
      if (r.distSq < best.distSq) {
        best = { a: na, b: nb, t: r.t, distSq: r.distSq, x: r.cx, y: r.cy };
      }
    }

    if (currentEdge.a && currentEdge.b) {
      const cur = pointToSegment(
        svgX,
        svgY,
        currentEdge.a.x,
        currentEdge.a.y,
        currentEdge.b.x,
        currentEdge.b.y,
      );
      if (cur.distSq < best.distSq + EDGE_SNAP_HYSTERESIS_SQ) {
        return {
          a: currentEdge.a,
          b: currentEdge.b,
          t: cur.t,
          distSq: cur.distSq,
          x: cur.cx,
          y: cur.cy,
        };
      }
    }

    currentEdge = best;
    return best;
  }

  const CAMPUS_BOUNDS = {
    minLat: 14.6192,
    maxLat: 14.6206,
    minLng: 121.1516,
    maxLng: 121.154,
  };

  function isInsideCampus(lat, lng) {
    return (
      lat >= CAMPUS_BOUNDS.minLat &&
      lat <= CAMPUS_BOUNDS.maxLat &&
      lng >= CAMPUS_BOUNDS.minLng &&
      lng <= CAMPUS_BOUNDS.maxLng
    );
  }

  function resolveNearestCampusAnchor(lat, lng, accuracyMeters = null) {
    if (isInsideCampus(lat, lng) || debugDisableGateAnchor) {
      return null;
    }

    const nearestGate = nearestCampusGate(lat, lng);
    const gateThreshold = accuracyMeters
      ? Math.max(250, accuracyMeters * 6.5)
      : 350;

    if (!nearestGate || nearestGate.distance > gateThreshold) {
      return null;
    }

    const gateNode = NODE_MAP[nearestGate.id];
    return gateNode || null;
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

    if (/vms\b|vicente\s+m/i.test(s)) return "VMS";
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
  const MOBILE_USER_AGENT =
    /Mobi|Android|iPhone|iPad|iPod|Windows Phone|Opera Mini|IEMobile/i;

  let overlayEl = null;
  let clipWrapEl = null;
  let mapWrapEl = null;
  let userDot = null;
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
  let gpsPermissionDenied = false;
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

  let smoothedLat = null;
  let smoothedLng = null;
  const SMOOTHING_ALPHA = 0.35;

  let labelItems = [];
  let poiItems = [];
  let transformScheduled = false;
  let gestureActive = false;
  let lastAppliedAngle = null;
  let lastAppliedScale = null;

  let smoothAngle = 0;
  const HEADING_ALPHA = 0.08;
  const METERS_PER_PIXEL = 0.045;
  const WALK_SPEED_M_PER_MIN = 80;
  const MIN_WALK_MINUTES = 1;

  let currentEdge = { a: null, b: null, t: 0, x: 0, y: 0 };
  let currentPath = [];
  let currentPathIdx = 0;
  let pathTraveledLine = null;
  let pathRemainingLine = null;
  let pathTraveledCasing = null;
  let pathRemainingCasing = null;
  const EDGE_SNAP_HYSTERESIS_SQ = 900;
  const GPS_DEVIATION_TOLERANCE = 3;

  var breadcrumbs = [];
  var breadcrumbGroup = null;
  const BREADCRUMB_MAX = 80;
  const BREADCRUMB_FADE_SEC = 90;

  var startFlag = null;
  var startFlagSet = false;

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

    if (!gestureActive) {
      updateLabelRotations();
      updateUserDotHeading();
      updateCompass();
      updatePinScaling();
    }
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
  let pullIndicatorEl = null;
  let refreshInProgress = false;
  let pullSafetyTimer = null;
  const pullState = {
    active: false,
    locked: false,
    isPull: false,
    ready: false,
    startX: 0,
    startY: 0,
    distance: 0,
  };
  const PULL_TRIGGER_ZONE = 0.2;
  const PULL_THRESHOLD = 80;
  const PULL_MAX = 110;

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
          .path-line { stroke-dasharray: 24 16; animation: none; }
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

    const pathRemainingCasingEl = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "polyline",
    );
    pathRemainingCasingEl.setAttribute("fill", "none");
    pathRemainingCasingEl.setAttribute("stroke", "#0a4d2b");
    pathRemainingCasingEl.setAttribute("stroke-width", "20");
    pathRemainingCasingEl.setAttribute("stroke-linecap", "round");
    pathRemainingCasingEl.setAttribute("stroke-linejoin", "round");
    pathRemainingCasingEl.setAttribute("opacity", "0");
    overlayEl.appendChild(pathRemainingCasingEl);
    pathRemainingCasing = pathRemainingCasingEl;

    pathRemainingLine = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "polyline",
    );
    pathRemainingLine.setAttribute("fill", "none");
    pathRemainingLine.setAttribute("stroke", "#1a73e8");
    pathRemainingLine.setAttribute("stroke-width", "10");
    pathRemainingLine.setAttribute("stroke-linecap", "round");
    pathRemainingLine.setAttribute("stroke-linejoin", "round");
    pathRemainingLine.setAttribute("opacity", "0");
    overlayEl.appendChild(pathRemainingLine);

    const pathTraveledCasingEl = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "polyline",
    );
    pathTraveledCasingEl.setAttribute("fill", "none");
    pathTraveledCasingEl.setAttribute("stroke", "#0a4d2b");
    pathTraveledCasingEl.setAttribute("stroke-width", "20");
    pathTraveledCasingEl.setAttribute("stroke-linecap", "round");
    pathTraveledCasingEl.setAttribute("stroke-linejoin", "round");
    pathTraveledCasingEl.setAttribute("opacity", "0");
    pathTraveledCasingEl.setAttribute("stroke-dasharray", "10 6");
    overlayEl.appendChild(pathTraveledCasingEl);
    pathTraveledCasing = pathTraveledCasingEl;

    pathTraveledLine = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "polyline",
    );
    pathTraveledLine.setAttribute("fill", "none");
    pathTraveledLine.setAttribute("stroke", "#4ade80");
    pathTraveledLine.setAttribute("stroke-width", "10");
    pathTraveledLine.setAttribute("stroke-linecap", "round");
    pathTraveledLine.setAttribute("stroke-linejoin", "round");
    pathTraveledLine.setAttribute("opacity", "0");
    pathTraveledLine.setAttribute("stroke-dasharray", "10 6");
    overlayEl.appendChild(pathTraveledLine);

    destMarker = document.createElementNS("http://www.w3.org/2000/svg", "g");
    destMarker.setAttribute("opacity", "0");
    destMarker.innerHTML = `
      <circle r="28" fill="#0a6635"/>
      <circle r="20" fill="#00c45e"/>
      <g transform="translate(-10.56,-14.08) scale(0.055)">
        <path fill="white" d="M215.7 499.2C267 435 384 279.4 384 192C384 86 298 0 192 0S0 86 0 192c0 87.4 117 243 176.3 307.2c9.4 10.1 22.6 15.9 36.4 15.9s27-5.8 36.4-15.9zM192 128a64 64 0 1 1 0 128 64 64 0 1 1 0-128z"/>
      </g>
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
      <circle r="11" fill="#1a73e8" stroke="#ffffff" stroke-width="3"/>
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

    breadcrumbGroup = document.createElementNS("http://www.w3.org/2000/svg", "g");
    breadcrumbGroup.id = "breadcrumbGroup";
    overlayEl.appendChild(breadcrumbGroup);

    startFlag = document.createElementNS("http://www.w3.org/2000/svg", "g");
    startFlag.setAttribute("opacity", "0");
    startFlag.innerHTML =
      '<circle r="16" fill="#0a6635" opacity="0.25"/>' +
      '<circle r="8" fill="#10b981" stroke="#0a6635" stroke-width="3"/>';
    overlayEl.appendChild(startFlag);

    labelGroupEl = document.createElementNS("http://www.w3.org/2000/svg", "g");
    labelGroupEl.id = "labelGroup";
    overlayEl.appendChild(labelGroupEl);

    BUILDING_LABELS.forEach((b) => {
      const PAD_X = 30,
        R = 22;
      const lines = Array.isArray(b.lines) ? b.lines : [b.label];
      const fontSize = lines.length > 1 ? 24 : 34;
      const lineHeight = lines.length > 1 ? 20 : 34;
      const approxW =
        Math.max(
          ...lines.map((line) => line.length * (lines.length > 1 ? 14 : 18)),
        ) +
        PAD_X * 2;
      const approxH = Math.max(52, lineHeight * lines.length + 26);

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
      g.appendChild(pill);

      const t = document.createElementNS("http://www.w3.org/2000/svg", "text");
      t.setAttribute("x", b.x);
      t.setAttribute("y", b.y - (lines.length - 1) * 10);
      t.setAttribute("text-anchor", "middle");
      t.setAttribute("dominant-baseline", "central");
      t.setAttribute("font-size", String(fontSize));
      t.setAttribute("font-weight", "800");
      t.setAttribute("font-family", "'Plus Jakarta Sans', sans-serif");
      t.setAttribute("fill", "#0a4d2b");
      t.setAttribute("pointer-events", "none");
      lines.forEach((line, idx) => {
        const tsp = document.createElementNS(
          "http://www.w3.org/2000/svg",
          "tspan",
        );
        tsp.setAttribute("x", b.x);
        tsp.setAttribute("dy", idx === 0 ? 0 : lineHeight);
        tsp.textContent = line;
        if (idx === 0) {
          tsp.setAttribute("dy", idx === 0 ? 0 : lineHeight);
        }
        t.appendChild(tsp);
      });
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

    buildNodeDebugLayer();
    buildCompass();
    startCompass();
    buildZoomControls();
    buildLocateButton();
    buildResetViewButton();
    buildSearchBar();
    buildLocatingToast();
    injectPullStyles();
    buildPullToRefresh();

    popupEl = document.createElement("div");
    popupEl.id = "mapPopup";
    popupEl.style.cssText = `
      position:absolute; left:0; right:0; bottom:0; z-index:50;
      pointer-events:none;
      display:flex; justify-content:center;
      transform:translateY(100%);
      transition:transform 0.28s cubic-bezier(.32,.72,0,1);
    `;
    mapWrapEl.appendChild(popupEl);

    fitMap();
    bindPanZoom();
    startGPS();

    buildTestMode();

    overlayEl.appendChild(userDot);

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

  function buildResetViewButton() {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.id = "mapResetBtn";
    btn.setAttribute("aria-label", "Reset map view");
    btn.innerHTML = `<i class="fas fa-expand"></i>`;
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      smoothAngle = 0;
      viewAngle = 0;
      autoCenterOnLocation = false;
      fitMap();
      if (currentUserNode) {
        const userNode = NODE_MAP[currentUserNode];
        if (userNode) {
          snapToUser(userNode.x, userNode.y);
        }
      }
    });
    mapWrapEl.appendChild(btn);
  }

  function jumpToUser() {
    if (gpsPermissionDenied || (!currentUserNode && gpsErrorShown)) {
      showLocatingToast("Enable location to see your position on the map.");
      return;
    }
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

  function injectPullStyles() {
    if (document.getElementById("mapPullStyles")) return;
    const style = document.createElement("style");
    style.id = "mapPullStyles";
    style.textContent = `
      #mapPullIndicator .pull-spinner i.spinning {
        animation: mapPullSpin 0.8s linear infinite;
      }
      @keyframes mapPullSpin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }
    `;
    document.head.appendChild(style);
  }

  function buildPullToRefresh() {
    pullIndicatorEl = document.createElement("div");
    pullIndicatorEl.id = "mapPullIndicator";
    pullIndicatorEl.innerHTML = `
      <span class="pull-spinner"><i class="fas fa-arrow-rotate-right"></i></span>
      <span class="pull-label">Pull to refresh location</span>
    `;
    pullIndicatorEl.style.cssText = `
      position:absolute; top:10px; left:50%;
      transform:translate(-50%,-70px);
      display:flex; align-items:center; gap:8px;
      background:rgba(255,255,255,0.96); color:#0a6635;
      font-size:13px; font-weight:700;
      padding:8px 16px; border-radius:999px;
      box-shadow:0 3px 14px rgba(0,40,20,0.18);
      z-index:60; pointer-events:none;
      transition:transform 0.25s ease, opacity 0.25s ease;
      opacity:0; white-space:nowrap;
    `;
    mapWrapEl.appendChild(pullIndicatorEl);
  }

  function updatePullIndicator(distance, ready) {
    if (!pullIndicatorEl) return;
    const clamped = Math.min(distance, PULL_MAX);
    const progress = Math.min(clamped / PULL_THRESHOLD, 1);
    pullIndicatorEl.style.transition = "none";
    pullIndicatorEl.style.transform = `translate(-50%, ${-70 + clamped}px)`;
    pullIndicatorEl.style.opacity = String(progress);
    const icon = pullIndicatorEl.querySelector(".pull-spinner i");
    const label = pullIndicatorEl.querySelector(".pull-label");
    if (icon) icon.style.transform = `rotate(${progress * 180}deg)`;
    if (label) {
      label.textContent = ready
        ? "Release to refresh location"
        : "Pull to refresh location";
    }
  }

  function showPullIndicator(state) {
    if (!pullIndicatorEl) return;
    pullIndicatorEl.style.transition =
      "transform 0.25s ease, opacity 0.25s ease";
    const icon = pullIndicatorEl.querySelector(".pull-spinner i");
    const label = pullIndicatorEl.querySelector(".pull-label");
    if (state === "refreshing") {
      pullIndicatorEl.style.transform = "translate(-50%, 14px)";
      pullIndicatorEl.style.opacity = "1";
      if (icon) {
        icon.classList.add("spinning");
        icon.style.transform = "";
      }
      if (label) label.textContent = "Refreshing your location…";
    }
  }

  function hidePullIndicator() {
    if (!pullIndicatorEl) return;
    pullIndicatorEl.style.transition =
      "transform 0.25s ease, opacity 0.25s ease";
    pullIndicatorEl.style.transform = "translate(-50%,-70px)";
    pullIndicatorEl.style.opacity = "0";
    const icon = pullIndicatorEl.querySelector(".pull-spinner i");
    if (icon) icon.classList.remove("spinning");
  }

  function triggerLocationRefresh() {
    if (refreshInProgress) return;
    refreshInProgress = true;
    showPullIndicator("refreshing");
    if (watchId !== null && navigator.geolocation) {
      navigator.geolocation.clearWatch(watchId);
      watchId = null;
    }
    gpsErrorShown = false;
    firstFix = true;
    startGPS();

    clearTimeout(pullSafetyTimer);
    pullSafetyTimer = setTimeout(() => {
      if (refreshInProgress) {
        refreshInProgress = false;
        hidePullIndicator();
      }
    }, 8000);
  }

  var testModeActive = false;
  var testModePin = null;

  function buildTestMode() {
    if (!mapWrapEl || !overlayEl) return;

    testModePin = document.createElementNS("http://www.w3.org/2000/svg", "circle");
    testModePin.setAttribute("r", "14");
    testModePin.setAttribute("fill", "#f59e0b");
    testModePin.setAttribute("stroke", "#fff");
    testModePin.setAttribute("stroke-width", "3");
    testModePin.setAttribute("opacity", "0");
    testModePin.style.cursor = "default";
    testModePin.style.pointerEvents = "none";
    overlayEl.appendChild(testModePin);

    var testToggleBtn = document.createElement("button");
    testToggleBtn.type = "button";
    testToggleBtn.id = "mapTestBtn";
    testToggleBtn.textContent = "TEST";
    testToggleBtn.style.cssText = [
      "display:none","background:#f59e0b","color:#000","font-size:11px","font-weight:800",
      "padding:6px 12px","border-radius:8px","border:none","cursor:pointer",
      "box-shadow:0 3px 14px rgba(0,40,20,0.18)","flex-shrink:0","min-width:44px",
    ].join(";");

    var searchWrap = document.querySelector(".map-search-row");
    if (searchWrap) {
      searchWrap.appendChild(testToggleBtn);
    } else {
      mapWrapEl.appendChild(testToggleBtn);
      testToggleBtn.style.cssText += ";position:absolute;top:14px;right:14px;z-index:41;";
    }

    var testPanelEl = document.createElement("div");
    testPanelEl.id = "mapTestPanel";
    testPanelEl.style.cssText = [
      "position:absolute","top:100%","right:14px","z-index:50",
      "background:#1a1a2e","color:#fff","border-radius:10px",
      "padding:12px","min-width:180px","display:none",
      "font-size:12px","font-family:monospace",
      "box-shadow:0 4px 20px rgba(0,0,0,0.5)","max-height:70vh","overflow-y:auto",
      "margin-top:6px",
    ].join(";");

    var presetGates = ["gate1","gate2","gate3","gate4","gate5"];
    var presetBldgs = ["b_vms","b_jsb","b_sjbh","b_sch","b_spcb","b_canteen","b_lounge","1a","1b","2a","3a"];

    var html = '<div style="font-weight:800;margin-bottom:8px;color:#f59e0b;">TEST MODE</div>';
    html += '<div style="margin:6px 0 10px;color:#aaa;font-size:10px;">Click map to place yourself</div>';

    html += '<div style="font-weight:600;margin:8px 0 4px;color:#60a5fa;">Gates:</div>';
    presetGates.forEach(function(g){
      var n = NODE_MAP[g]; if(!n) return;
      html += '<button class="tm-preset" data-node="'+g+'" style="display:block;width:100%;text-align:left;padding:4px 8px;margin:2px 0;background:#333;border:none;color:#fff;border-radius:4px;cursor:pointer;font-family:monospace;font-size:11px;">'+g+' ('+Math.round(n.x)+','+Math.round(n.y)+')</button>';
    });

    html += '<div style="font-weight:600;margin:8px 0 4px;color:#60a5fa;">Buildings:</div>';
    presetBldgs.forEach(function(g){
      var n = NODE_MAP[g]; if(!n) return;
      html += '<button class="tm-preset" data-node="'+g+'" style="display:block;width:100%;text-align:left;padding:4px 8px;margin:2px 0;background:#333;border:none;color:#fff;border-radius:4px;cursor:pointer;font-family:monospace;font-size:11px;">'+g+' ('+Math.round(n.x)+','+Math.round(n.y)+')</button>';
    });

    html += '<button id="tmClear" style="display:block;width:100%;padding:6px;margin-top:10px;background:#dc2626;border:none;color:#fff;border-radius:4px;cursor:pointer;font-weight:600;">Clear & Exit Test</button>';
    testPanelEl.innerHTML = html;

    var headerBar = document.getElementById("mapHeaderBar");
    if (headerBar) {
      headerBar.appendChild(testPanelEl);
    } else {
      mapWrapEl.appendChild(testPanelEl);
    }

    var nodeBtn = document.createElement("button");
    nodeBtn.type = "button";
    nodeBtn.id = "mapNodeBtn";
    nodeBtn.textContent = "NODES";
    nodeBtn.style.cssText = [
      "display:none","background:#111","color:#fff","font-size:11px","font-weight:800",
      "padding:6px 12px","border-radius:8px","border:none","cursor:pointer",
      "box-shadow:0 3px 14px rgba(0,40,20,0.18)","flex-shrink:0","min-width:44px",
    ].join(";");
    nodeBtn.addEventListener("click", function(e){
      e.stopPropagation();
      toggleNodeDebug();
    });
    nodeBtn.style.background = nodeDebugVisible ? "#10b981" : "#111";
    if (searchWrap) searchWrap.appendChild(nodeBtn);

    testToggleBtn.addEventListener("click", function(e){
      e.stopPropagation();
      testModeActive = !testModeActive;
      testPanelEl.style.display = testModeActive ? "block" : "none";
      testToggleBtn.style.background = testModeActive ? "#10b981" : "#f59e0b";
      testToggleBtn.style.color = testModeActive ? "#fff" : "#000";
      testToggleBtn.textContent = testModeActive ? "TEST: ON" : "TEST";
      if (!testModeActive) testModePin.setAttribute("opacity","0");
    });

    testPanelEl.querySelector("#tmClear").addEventListener("click", function(e){
      e.stopPropagation();
      testModeActive = false;
      testPanelEl.style.display = "none";
      testToggleBtn.style.background = "#f59e0b";
      testToggleBtn.style.color = "#000";
      testToggleBtn.textContent = "TEST";
      testModePin.setAttribute("opacity","0");
      clearAllPaths();
      clearBreadcrumbs();
      startFlagSet = false;
      if (startFlag) startFlag.setAttribute("opacity", "0");
    });

    testPanelEl.querySelectorAll(".tm-preset").forEach(function(btn){
      btn.addEventListener("click", function(e){
        e.stopPropagation();
        var nodeId = btn.getAttribute("data-node");
        var node = NODE_MAP[nodeId];
        if (!node) return;
        simTestPosition(node.x, node.y, nodeId);
      });
    });

    overlayEl.addEventListener("click", function(e){
      if (!testModeActive) return;
      if (e.target !== overlayEl) return;
      e.stopPropagation();
      var pt = overlayEl.createSVGPoint();
      pt.x = e.clientX;
      pt.y = e.clientY;
      var svgP = pt.matrixTransform(overlayEl.getScreenCTM().inverse());
      simTestPosition(svgP.x, svgP.y, null);
    }, true);
  }

  function simTestPosition(svgX, svgY, forceNodeId) {
    gpsErrorShown = false;
    gpsPermissionDenied = false;
    firstFix = false;

    var edgeSnap = snapToNearestEdge(svgX, svgY);
    currentEdge = edgeSnap;
    testModePin.setAttribute("cx", edgeSnap.x);
    testModePin.setAttribute("cy", edgeSnap.y);
    testModePin.setAttribute("opacity", "1");

    moveUserDot(edgeSnap.x, edgeSnap.y);

    var prevUserNode = currentUserNode;

    if (forceNodeId) {
      currentUserNode = forceNodeId;
    } else {
      var nodeSnap = nearestNode(edgeSnap.x, edgeSnap.y, null, 15);
      if (nodeSnap) {
        currentUserNode = nodeSnap.id;
      } else if (edgeSnap.a) {
        currentUserNode = edgeSnap.t <= 0.5 ? edgeSnap.a.id : edgeSnap.b.id;
      }
    }

    if (prevUserNode && currentUserNode && prevUserNode !== currentUserNode) {
      var pathNodes = aStar(prevUserNode, currentUserNode);
      if (pathNodes && pathNodes.length > 0) {
        var pts = [];
        for (var p = 0; p < pathNodes.length; p++) {
          var pn = NODE_MAP[pathNodes[p]];
          if (pn) pts.push({ x: pn.x, y: pn.y });
        }
        if (pts.length > 0) addBreadcrumbBulk(pts);
      }
    } else {
      addBreadcrumb(edgeSnap.x, edgeSnap.y);
    }

    currentPath = [];
    currentPathIdx = 0;
    if (destBuildingId) {
      drawRoute();
    } else {
      clearAllPaths();
      var bar = document.getElementById("mapDirectionsBar");
      if (bar) bar.style.display = "none";
    }
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
    const aliasMap = {
      VMS: ["admissions", "registrar", "records", "enrollment"],
      JSB: ["guidance", "counseling", "lrc", "library", "resource center"],
      SJBH: ["accounting", "finance", "billing", "scholarship", "financial"],
      SCH: ["clinic", "health", "infirmary", "services"],
      SPCB: ["engineering", "it", "computer", "tech", "lab"],
      CANTEEN: ["canteen", "faculty", "cafeteria", "food"],
    };
    if (q) {
      matches = BUILDINGS_INFO.filter((b) => {
        const hay = [
          b.name,
          b.short,
          b.category,
          b.offices.join(" "),
          (aliasMap[b.id] || []).join(" "),
        ]
          .join(" ")
          .toLowerCase();
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
        goToBuilding(b, q || b.name);
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

  function goToBuilding(b, officeName = null) {
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
    const resolvedId = b.id;
    if (resolvedId !== destBuildingId) {
      startFlagSet = false;
      if (startFlag) startFlag.setAttribute("opacity", "0");
    }
    destBuildingId = resolvedId;
    updateHeader();
    if (mapInitialized) {
      drawRoute();
    }
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
      display:none; position:absolute; top:14px; right:62px; z-index:41;
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
  }

  function toggleNodeDebug(force) {
    if (!nodeDebugGroupEl) return;
    nodeDebugVisible = force !== undefined ? force : !nodeDebugVisible;
    nodeDebugGroupEl.style.display = nodeDebugVisible ? "block" : "none";
    var nb = document.getElementById("mapNodeBtn");
    if (nb) nb.style.background = nodeDebugVisible ? "#10b981" : "#111";
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
    window.removeEventListener(
      "deviceorientationabsolute",
      onOrientation,
      true,
    );
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
        gestureActive = true;
        if (e.touches.length === 1) {
          const rect = mapWrapEl.getBoundingClientRect();
          const touchY = e.touches[0].clientY - rect.top;
          pullState.active =
            touchY < mapWrapEl.clientHeight * PULL_TRIGGER_ZONE &&
            !refreshInProgress;
          pullState.locked = false;
          pullState.isPull = false;
          pullState.ready = false;
          pullState.distance = 0;

          gestureMode = "pan";
          isPanning = true;
          autoCenterOnLocation = false;
          panStartX = e.touches[0].clientX;
          panStartY = e.touches[0].clientY;
          panOriginX = viewX;
          panOriginY = viewY;
        } else if (e.touches.length === 2) {
          pullState.active = false;
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
        if (e.touches.length === 1 && gestureMode === "pan") {
          const dx = e.touches[0].clientX - panStartX;
          const dy = e.touches[0].clientY - panStartY;

          if (pullState.active && !pullState.locked) {
            if (dy > 10 && Math.abs(dy) > Math.abs(dx)) {
              pullState.isPull = true;
              pullState.locked = true;
              isPanning = false;
            } else if (Math.abs(dx) > 10 || dy < -10) {
              pullState.locked = true;
            }
          }

          if (pullState.isPull) {
            pullState.distance = Math.min(Math.max(0, dy), PULL_MAX);
            pullState.ready = pullState.distance >= PULL_THRESHOLD;
            updatePullIndicator(pullState.distance, pullState.ready);
          } else if (isPanning) {
            const cos = Math.cos(-viewAngle);
            const sin = Math.sin(-viewAngle);
            viewX = panOriginX + (dx * cos - dy * sin);
            viewY = panOriginY + (dx * sin + dy * cos);
            clampPan();
            applyTransform();
          }
        } else if (e.touches.length === 2 && gestureMode === "pinch") {
          const d = getTouchDist(e.touches);
          const ang = getTouchAngle(e.touches);
          const rect = mapWrapEl.getBoundingClientRect();
          const cx =
            (e.touches[0].clientX + e.touches[1].clientX) / 2 - rect.left;
          const cy =
            (e.touches[0].clientY + e.touches[1].clientY) / 2 - rect.top;

          if (lastTouchDist) {
            const factor = d / lastTouchDist / (viewScale / lastPinchScale);
            zoomAt(cx, cy, factor);
          }

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
      if (pullState.isPull) {
        if (pullState.ready && !refreshInProgress) {
          triggerLocationRefresh();
        } else {
          hidePullIndicator();
        }
        pullState.active = false;
        pullState.locked = false;
        pullState.isPull = false;
        pullState.ready = false;
        pullState.distance = 0;
      }

      if (e.touches.length === 0) {
        gestureActive = false;
        isPanning = false;
        gestureMode = null;
        lastTouchDist = null;
        lastTwistAngle = null;
        scheduleTransform();
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
      gestureActive = false;
      isPanning = false;
      isRotating = false;
      el.style.cursor = "grab";
      scheduleTransform();
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
         <i class="fas fa-ticket"></i><span>Your destination</span>
       </div>`
      : "";

    popupEl.innerHTML = `
    <div class="popup-card" style="pointer-events:all">
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

    popupEl.querySelector(".popup-close").addEventListener("click", (ev) => {
      ev.stopPropagation();
      hidePopup();
    });

    requestAnimationFrame(() => {
      popupEl.style.transform = "translateY(0)";
    });
  }

  function hidePopup() {
    if (!popupEl) return;
    popupEl.style.transform = "translateY(100%)";
    activePopupSVG = null;
  }

  function showYouAreHerePopup(e) {
    if (!popupEl || !mapWrapEl) return;

    popupEl.innerHTML = `
    <div class="popup-card youarehere-card" style="pointer-events:all">
      <div class="popup-body" style="padding-top:16px">
        <div style="display:flex;align-items:center;gap:10px">
          <i class="fas fa-location-dot" style="color:#1a73e8;font-size:16px"></i>
          <div class="popup-title" style="margin:0">You are here</div>
        </div>
      </div>
    </div>
  `;

    requestAnimationFrame(() => {
      popupEl.style.transform = "translateY(0)";
    });
  }

  function startGPS() {
    if (!navigator.geolocation) {
      gpsPermissionDenied = true;
      showGPSError(
        "Location isn't supported on this device — directions can't be shown.",
      );
      drawRoute();
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
    let msg = "Location unavailable — directions can't be shown right now.";
    if (err && err.code === err.PERMISSION_DENIED) {
      gpsPermissionDenied = true;
      msg =
        "Location permission denied — enable it in your browser settings to see directions.";
    } else if (err && err.code === err.TIMEOUT) {
      msg = "Couldn't get your location — retrying…";
    }
    showGPSError(msg);
    if (err && err.code !== err.TIMEOUT) {
      drawRoute();
    }
  }

  var gpsDebugPanelEl = null;

  function ensureGPSDebugPanel() {
    if (!mapWrapEl) return null;
    if (gpsDebugPanelEl) return gpsDebugPanelEl;
    var panel = document.getElementById("gpsDebugPanel");
    if (!panel) {
      panel = document.createElement("div");
      panel.id = "gpsDebugPanel";
      panel.style.cssText = [
        "position:absolute",
        "left:12px",
        "bottom:52px",
        "z-index:30",
        "background:rgba(17,24,39,0.85)",
        "color:#fff",
        "font-size:10px",
        "line-height:1.4",
        "padding:8px 10px",
        "border-radius:8px",
        "font-family:monospace",
        "pointer-events:none",
        "max-width:280px",
      ].join(";");
      mapWrapEl.appendChild(panel);
    }
    gpsDebugPanelEl = panel;
    return panel;
  }

  function updateDebugPanel(data) {
    var panel = ensureGPSDebugPanel();
    if (!panel) return;
    panel.innerHTML =
      "GPS raw: " + data.rawLat + ", " + data.rawLng + "<br>" +
      "Acc: " + data.acc + " | inside: " + data.inside + "<br>" +
      "Gate anchor: " + data.anchor + "<br>" +
      "Smooth: " + data.smoothLat + ", " + data.smoothLng + "<br>" +
      "SVG raw: (" + data.svgX + ", " + data.svgY + ")<br>" +
      "Edge: " + data.edge + " snap(" + data.snapX + ", " + data.snapY + ")<br>" +
      "Node: " + data.node + " (" + data.nodeX + ", " + data.nodeY + ")";
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

  function handlePathProgress(nodeId) {
    if (!currentPath.length || currentPathIdx >= currentPath.length - 1) return false;
    var nextNodeId = currentPath[currentPathIdx + 1];
    if (nodeId === nextNodeId) {
      currentPathIdx++;
      return true;
    }
    if (nodeId !== currentPath[currentPathIdx] && currentPath.indexOf(nodeId) === -1) {
      return true;
    }
    return false;
  }

  function onPosition(pos) {
    if (refreshInProgress) {
      refreshInProgress = false;
      clearTimeout(pullSafetyTimer);
      hidePullIndicator();
    }
    if (gpsErrorShown) {
      gpsErrorShown = false;
      gpsPermissionDenied = false;
      if (!destBuildingId) {
        const bar = document.getElementById("mapDirectionsBar");
        if (bar) bar.style.display = "none";
      }
    }

    const accuracy = pos.coords.accuracy ?? GPS_ACCEPTABLE_ACCURACY;
    const maxAccuracy = Math.min(
      GPS_MAX_ACCEPTABLE_ACCURACY,
      Math.max(GPS_ACCEPTABLE_ACCURACY, accuracy),
    );

    const { latitude: lat, longitude: lng } = pos.coords;
    const nearestGate = nearestCampusGate(lat, lng);
    const gateAnchor = resolveNearestCampusAnchor(lat, lng, accuracy);
    const insideCampus = isInsideCampus(lat, lng);

    if (
      accuracy > maxAccuracy &&
      (!nearestGate || nearestGate.distance > 350)
    ) {
      if (currentUserNode) {
        const prev = NODE_MAP[currentUserNode];
        if (prev) {
          moveUserDot(prev.x, prev.y);
        }
      }
      return;
    }

    const weighting = Math.min(1, 30 / Math.max(accuracy, 10));

    if (smoothedLat === null) {
      smoothedLat = lat;
      smoothedLng = lng;
    } else {
      smoothedLat += weighting * (lat - smoothedLat);
      smoothedLng += weighting * (lng - smoothedLng);
    }

    const projectedLat = gateAnchor ? nearestGate.lat : smoothedLat;
    const projectedLng = gateAnchor ? nearestGate.lng : smoothedLng;
    const svgPos = gpsToSvg(projectedLat, projectedLng);
    const edgeSnap = snapToNearestEdge(svgPos.x, svgPos.y);
    const snapped = gateAnchor
      ? gateAnchor
      : nearestNode(svgPos.x, svgPos.y, currentUserNode, accuracy);

    if (snapped && (!insideCampus || !gateAnchor)) {
      currentUserNode = snapped.id;
      moveUserDot(edgeSnap.x, edgeSnap.y);
      addBreadcrumb(edgeSnap.x, edgeSnap.y);
      if (destBuildingId) {
        if (handlePathProgress(snapped.id)) drawRoute();
        else updatePathProgress();
      }

      const isFirstFix = firstFix;

      if (isFirstFix) {
        firstFix = false;
        autoCenterOnLocation = false;
        snapToUser(edgeSnap.x, edgeSnap.y);
        drawRoute();
        if (locatingToastEl) {
          locatingToastEl.classList.remove("show");
        }
      } else if (autoCenterOnLocation) {
        centerOnUser(edgeSnap.x, edgeSnap.y);
      }
      return;
    }

    if (insideCampus) {
      const campusSnap = nearestNode(
        svgPos.x,
        svgPos.y,
        currentUserNode,
        accuracy,
      );
      if (campusSnap) {
        currentUserNode = campusSnap.id;
        moveUserDot(edgeSnap.x, edgeSnap.y);
        addBreadcrumb(edgeSnap.x, edgeSnap.y);
        if (destBuildingId) {
          if (handlePathProgress(campusSnap.id)) drawRoute();
          else updatePathProgress();
        }

        if (firstFix) {
          firstFix = false;
          autoCenterOnLocation = false;
          snapToUser(edgeSnap.x, edgeSnap.y);
          drawRoute();
          if (locatingToastEl) {
            locatingToastEl.classList.remove("show");
          }
        } else if (autoCenterOnLocation) {
          centerOnUser(edgeSnap.x, edgeSnap.y);
        }
        return;
      }
    }

    if (currentUserNode) {
      const prev = NODE_MAP[currentUserNode];
      if (prev) {
        moveUserDot(prev.x, prev.y);
        addBreadcrumb(prev.x, prev.y);
        if (destBuildingId) drawRoute();
        return;
      }
    }

    if (nearestGate) {
      const gateNode = NODE_MAP[nearestGate.id];
      if (gateNode) {
        currentUserNode = gateNode.id;
        moveUserDot(edgeSnap.x, edgeSnap.y);
        addBreadcrumb(edgeSnap.x, edgeSnap.y);
        if (destBuildingId) drawRoute();
        return;
      }
    }

    if (firstFix) {
      firstFix = false;
      autoCenterOnLocation = false;
      if (locatingToastEl) {
        locatingToastEl.classList.remove("show");
      }
    }
  }

  function updateETAFromPosition(edgeSnap, nodeSnap) {
    var bar = document.getElementById("mapDirectionsBar");
    var txt = document.getElementById("mapDirectionsText");
    if (!bar || !txt) return;
    gpsErrorShown = false;
    var bInfo = BUILDING_INFO[destBuildingId];
    txt.textContent = "Head towards " + (bInfo ? bInfo.name : "your destination");
    bar.style.display = "flex";
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

  function addBreadcrumb(x, y) {
    var now = Date.now();
    var last = breadcrumbs.length ? breadcrumbs[breadcrumbs.length - 1] : null;
    if (last) {
      var dx = x - last.x, dy = y - last.y;
      if (dx * dx + dy * dy < 16) return;
    }
    breadcrumbs.push({ x: x, y: y, t: now });
    if (breadcrumbs.length > BREADCRUMB_MAX) breadcrumbs.shift();
    renderBreadcrumbs();
  }

  function addBreadcrumbBulk(points) {
    for (var i = 0; i < points.length; i++) {
      var p = points[i];
      var last = breadcrumbs.length ? breadcrumbs[breadcrumbs.length - 1] : null;
      if (last) {
        var dx = p.x - last.x, dy = p.y - last.y;
        if (dx * dx + dy * dy < 16) continue;
      }
      breadcrumbs.push({ x: p.x, y: p.y, t: Date.now() });
    }
    if (breadcrumbs.length > BREADCRUMB_MAX) breadcrumbs.splice(0, breadcrumbs.length - BREADCRUMB_MAX);
    renderBreadcrumbs();
  }

  function renderBreadcrumbs() {
    if (!breadcrumbGroup) return;
    var now = Date.now();
    var points = "";
    var totalElapsed = 0;
    for (var i = 0; i < breadcrumbs.length; i++) {
      var b = breadcrumbs[i];
      var elapsed = (now - b.t) / 1000;
      if (elapsed > BREADCRUMB_FADE_SEC) continue;
      points += b.x + "," + b.y + " ";
      if (i === breadcrumbs.length - 1) totalElapsed = elapsed;
    }
    if (!points) { breadcrumbGroup.innerHTML = ""; return; }
    var pts = points.trim();
    var alpha = Math.max(0.15, 1 - totalElapsed / BREADCRUMB_FADE_SEC);
    breadcrumbGroup.innerHTML =
      '<polyline points="' + pts + '" fill="none" stroke="#374151" stroke-width="20" stroke-linecap="round" stroke-linejoin="round" opacity="' + alpha.toFixed(2) + '"/>' +
      '<polyline points="' + pts + '" fill="none" stroke="#6b7280" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="8 4" opacity="' + alpha.toFixed(2) + '"/>';
  }

  function clearBreadcrumbs() {
    breadcrumbs = [];
    if (breadcrumbGroup) breadcrumbGroup.innerHTML = "";
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

  function clearAllPaths() {
    pathTraveledCasing.setAttribute("opacity", "0");
    pathTraveledLine.setAttribute("opacity", "0");
    pathRemainingCasing.setAttribute("opacity", "0");
    pathRemainingLine.setAttribute("opacity", "0");
    destMarker.setAttribute("opacity", "0");
    startFlagSet = false;
    if (startFlag) startFlag.setAttribute("opacity", "0");
  }

  function drawRoute() {
    if (!destBuildingId) { clearAllPaths(); return; }
    var destNodeId = BUILDING_NODE[destBuildingId];
    if (!destNodeId) { clearAllPaths(); return; }

    if (!currentUserNode) { clearAllPaths(); return; }

    if (currentUserNode === destNodeId) {
      clearAllPaths();
      destMarker.setAttribute("opacity", "1");
      var barA = document.getElementById("mapDirectionsBar");
      var txtA = document.getElementById("mapDirectionsText");
      if (barA && txtA) {
        txtA.textContent = "You've arrived!";
        barA.style.display = "flex";
      }
      currentPath = [];
      currentPathIdx = 0;
      return;
    }

    var pathIds = aStar(currentUserNode, destNodeId);
    if (!pathIds) { clearAllPaths(); return; }

    currentPath = pathIds;

    if (!startFlagSet && startFlag && pathIds.length > 0) {
      startFlagSet = true;
      var flagX = currentEdge.x !== undefined ? currentEdge.x : NODE_MAP[pathIds[0]].x;
      var flagY = currentEdge.y !== undefined ? currentEdge.y : NODE_MAP[pathIds[0]].y;
      startFlag.setAttribute("transform", "translate(" + flagX + "," + flagY + ")");
      startFlag.setAttribute("opacity", "1");
    }

    var nodeIdx = pathIds.indexOf(currentUserNode);
    if (nodeIdx === -1) {
      nodeIdx = 0;
      currentPathIdx = 0;
    } else {
      currentPathIdx = nodeIdx;
    }

    renderPathSplit(nodeIdx, NODE_MAP[pathIds[nodeIdx]].x, NODE_MAP[pathIds[nodeIdx]].y);

    var destNode = NODE_MAP[destNodeId];
    destMarker.setAttribute("transform", "translate(" + destNode.x + "," + destNode.y + ")");
    destMarker.setAttribute("opacity", "1");

    updateHeader();
    updateETA(pathIds, nodeIdx);
  }

  function updatePathProgress() {
    if (!currentPath.length || !destBuildingId || !currentUserNode) return;
    if (!currentEdge.a || !currentEdge.b) return;

    var traveledIdx = currentPathIdx;
    for (var i = currentPathIdx; i < currentPath.length - 1; i++) {
      var a = currentPath[i], b = currentPath[i + 1];
      var ea = currentEdge.a.id, eb = currentEdge.b.id;
      if ((ea === a && eb === b) || (ea === b && eb === a)) {
        traveledIdx = i;
        break;
      }
    }

    if (currentPath.indexOf(currentUserNode) === -1 && traveledIdx === currentPathIdx) {
      drawRoute();
      return;
    }

    renderPathSplit(traveledIdx, currentEdge.x, currentEdge.y);
    updateETAFromPosition(currentEdge, null);
  }

  function renderPathSplit(nodeIdx, splitX, splitY) {
    var tp = "";
    for (var t = 0; t <= nodeIdx; t++) {
      var tn = NODE_MAP[currentPath[t]];
      if (tn) tp += tn.x + "," + tn.y + " ";
    }
    tp += splitX + "," + splitY;

    var rp = splitX + "," + splitY + " ";
    for (var r = nodeIdx + 1; r < currentPath.length; r++) {
      var rn = NODE_MAP[currentPath[r]];
      if (rn) rp += rn.x + "," + rn.y + " ";
    }

    if (nodeIdx > 0) {
      pathTraveledCasing.setAttribute("points", tp.trim());
      pathTraveledCasing.setAttribute("opacity", "1");
      pathTraveledLine.setAttribute("points", tp.trim());
      pathTraveledLine.setAttribute("opacity", "1");
    } else {
      pathTraveledCasing.setAttribute("opacity", "0");
      pathTraveledLine.setAttribute("opacity", "0");
    }

    pathRemainingCasing.setAttribute("points", rp.trim());
    pathRemainingCasing.setAttribute("opacity", "1");
    pathRemainingLine.setAttribute("points", rp.trim());
    pathRemainingLine.setAttribute("opacity", "1");
  }

  function updateETA(pathIds, traveledIdx) {
    var bar = document.getElementById("mapDirectionsBar");
    var txt = document.getElementById("mapDirectionsText");
    if (!bar || !txt) return;
    gpsErrorShown = false;
    var bInfo = BUILDING_INFO[destBuildingId];
    txt.textContent = "Head towards " + (bInfo ? bInfo.name : "your destination");
    bar.style.display = "flex";
  }

  function updateHeader() {
    const bInfo = BUILDING_INFO[destBuildingId];
    const destEl = document.getElementById("mapDestName");
    if (destEl && bInfo) destEl.textContent = bInfo.short;
  }

  let debugDisableGateAnchor = false;

  window.CampusMap = {
    init: initMap,
    refit: fitMap,
    toggleNodes: toggleNodeDebug,
    toggleGateAnchor: function () { debugDisableGateAnchor = !debugDisableGateAnchor; return debugDisableGateAnchor; },
    debugData: function () { return { currentUserNode: currentUserNode, currentEdge: currentEdge, currentPath: currentPath, currentPathIdx: currentPathIdx }; },

    setDestination(officeName, buildingHint) {
      const id = resolveBuildingId(officeName, buildingHint);
      if (!id) return;
      if (id !== destBuildingId) {
        startFlagSet = false;
        if (startFlag) startFlag.setAttribute("opacity", "0");
      }
      destBuildingId = id;
      updateHeader();
      if (mapInitialized) {
        drawRoute();
      }
    },
  };
})();
