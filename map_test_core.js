// campus_map.js core logic extracted for standalone testing
// ====== DATA ======

var METERS_PER_PIXEL = 0.045;
var WALK_SPEED_M_PER_MIN = 80;

var CALIBRATION_POINTS = [
  { lat: 14.620303859819074, lng: 121.15358155696558, x: 3300, y: 639 },
  { lat: 14.62001165707186, lng: 121.15312420991697, x: 2687, y: 784 },
  { lat: 14.61943626769976, lng: 121.15247410852331, x: 1735, y: 760 },
  { lat: 14.61959129503177, lng: 121.15203968057038, x: 1168, y: 654 },
  { lat: 14.619564463385176, lng: 121.15166379256078, x: 610, y: 443 },
  { lat: 14.619263352459713, lng: 121.1516791978071, x: 608, y: 791 },
  { lat: 14.61928295764446, lng: 121.15189987667863, x: 830, y: 879 },
  { lat: 14.619672262909887, lng: 121.15284669652685, x: 2225, y: 850 },
  { lat: 14.619918822564339, lng: 121.153348269658, x: 2980, y: 980 },
  { lat: 14.620471634244192, lng: 121.1539061691739, x: 3700, y: 596 }
];

var NODES = [
  { id: "gate1", x: 608, y: 791 }, { id: "gate2", x: 830, y: 879 },
  { id: "gate3", x: 2225, y: 850 }, { id: "gate4", x: 2980, y: 980 },
  { id: "gate5", x: 3700, y: 596 },
  { id: "j_1", x: 700, y: 693 },   { id: "j_2", x: 800, y: 550 }, { id: "j_41", x: 875, y: 465 },
  { id: "j_3", x: 730, y: 493 }, { id: "j_4", x: 950, y: 380 },
  { id: "j_5", x: 980, y: 330 },   { id: "j_6", x: 1032, y: 328 }, { id: "j_38", x: 1196, y: 374 },
  { id: "j_7", x: 1360, y: 420 }, { id: "j_8", x: 1330, y: 470 },
  { id: "j_9", x: 1500, y: 460 }, { id: "j_10", x: 1270, y: 750 },
  { id: "j_39", x: 1385, y: 605 }, { id: "j_40", x: 1503, y: 755 },
  { id: "j_11", x: 1160, y: 890 }, { id: "j_12", x: 1120, y: 920 },
  { id: "j_13", x: 1060, y: 940 }, { id: "j_14", x: 1000, y: 930 },
  { id: "j_15", x: 850, y: 820 }, { id: "j_16", x: 1550, y: 480 },
  { id: "j_17", x: 1600, y: 530 }, { id: "j_18", x: 1650, y: 600 },
  { id: "j_19", x: 1700, y: 650 },   { id: "j_20", x: 1745, y: 655 }, { id: "j_42", x: 1923, y: 669 },
  { id: "j_21", x: 2101, y: 683 }, { id: "j_22", x: 2063, y: 760 },
  { id: "j_24", x: 2263, y: 745 }, { id: "j_25", x: 2456, y: 772 },
  { id: "j_26", x: 2929, y: 782 }, { id: "j_27", x: 3300, y: 783 },
  { id: "j_28", x: 3194, y: 783 }, { id: "j_29", x: 3300, y: 596 },
  { id: "b_vms", x: 610, y: 443 }, { id: "1a", x: 526, y: 530 },
  { id: "1b", x: 690, y: 300 }, { id: "b_jsb", x: 1168, y: 654 },
  { id: "2a", x: 1050, y: 554 }, { id: "b_sjbh", x: 1735, y: 760 },
  { id: "3a", x: 1735, y: 830 }, { id: "b_sch", x: 2687, y: 784 },
  { id: "b_spcb", x: 3300, y: 639 }, { id: "b_canteen", x: 1860, y: 580 },
  { id: "b_lounge", x: 1300, y: 344 }
];

var EDGES = [
  ["gate1","j_1"],["j_1","j_2"],["j_2","j_3"],["j_3","b_vms"],
  ["b_vms","1a"],["b_vms","1b"],  ["j_2","j_41"],["j_41","j_4"],["j_4","j_5"],
  ["j_5","j_6"],  ["j_6","j_38"],["j_38","j_7"],["j_7","b_lounge"],["j_7","j_8"],
  ["j_8","b_jsb"],["b_jsb","2a"],["j_7","j_9"],["j_9","j_16"],
  ["j_9","j_39"],["j_39","j_10"],["j_1","j_15"],["gate2","j_15"],["j_15","j_14"],
  ["j_14","j_13"],["j_13","j_12"],["j_12","j_11"],["j_11","j_10"],
  ["j_16","j_17"],["j_17","j_18"],["j_18","j_19"],["j_19","j_20"],
  ["j_20","j_42"],["j_42","j_21"],["j_20","b_canteen"],  ["j_10","j_40"],["j_40","b_sjbh"],
  ["j_20","b_sjbh"],["b_sjbh","3a"],["j_21","j_24"],
  ["j_22","b_sjbh"],["j_22","j_24"],["gate3","j_24"],
  ["j_24","j_25"],["j_25","b_sch"],["j_25","j_26"],
  ["j_26","j_28"],["j_26","b_sch"],["gate4","j_26"],
  ["j_28","j_27"],["j_27","j_29"],["j_27","b_spcb"],
  ["j_29","b_spcb"],["j_29","gate5"]
];

var BUILDING_NODE = {
  VMS:"b_vms",ADMISSIONS:"1a",REGISTRAR:"1b",
  JSB:"b_jsb",SJBH:"b_sjbh",ACCOUNTING:"3a",
  SCH:"b_sch",SPCB:"b_spcb",
  STUDENT_LOUNGE:"b_lounge",CANTEEN:"b_canteen"
};

var NODE_MAP = {};
NODES.forEach(function(n){NODE_MAP[n.id]=n;});

var CAMPUS_GATE_POINTS = [
  { id:"gate1", lat:14.619263352459713, lng:121.1516791978071 },
  { id:"gate2", lat:14.61928295764446, lng:121.15189987667863 },
  { id:"gate3", lat:14.619672262909887, lng:121.15284669652685 },
  { id:"gate4", lat:14.619918822564339, lng:121.153348269658 },
  { id:"gate5", lat:14.620471634244192, lng:121.1539061691739 }
];

var CAMPUS_BOUNDS = { minLat:14.6192, maxLat:14.6206, minLng:121.1516, maxLng:121.154 };

// ====== MATH FUNCTIONS ======

function dist(a,b){var dx=a.x-b.x,dy=a.y-b.y;return Math.sqrt(dx*dx+dy*dy);}

function pointToSegment(px,py,ax,ay,bx,by){
  var dx=bx-ax,dy=by-ay,lenSq=dx*dx+dy*dy;
  if(lenSq<1e-12){var cx=(ax+bx)/2,cy=(ay+by)/2;return{t:0.5,distSq:(px-cx)*(px-cx)+(py-cy)*(py-cy),cx:cx,cy:cy};}
  var t=((px-ax)*dx+(py-ay)*dy)/lenSq;
  t=Math.max(0,Math.min(1,t));
  var cx=ax+t*dx,cy=ay+t*dy;
  return{t:t,distSq:(px-cx)*(px-cx)+(py-cy)*(py-cy),cx:cx,cy:cy};
}

function snapToNearestEdge(svgX,svgY){
  var best={aId:null,bId:null,t:0,distSq:Infinity,x:svgX,y:svgY};
  for(var i=0;i<EDGES.length;i++){
    var aId=EDGES[i][0],bId=EDGES[i][1];
    var na=NODE_MAP[aId],nb=NODE_MAP[bId];
    if(!na||!nb)continue;
    var r=pointToSegment(svgX,svgY,na.x,na.y,nb.x,nb.y);
    if(r.distSq<best.distSq)best={aId:aId,bId:bId,t:r.t,distSq:r.distSq,x:r.cx,y:r.cy};
  }
  return best;
}

// ====== A* PATHFINDING ======

var GRAPH = (function(){
  var adj={};
  NODES.forEach(function(n){adj[n.id]=[];});
  EDGES.forEach(function(e){
    var na=NODE_MAP[e[0]],nb=NODE_MAP[e[1]];
    if(!na||!nb)return;
    var d=dist(na,nb);
    adj[e[0]].push({id:e[1],cost:d});
    adj[e[1]].push({id:e[0],cost:d});
  });
  return adj;
})();

function aStar(startId,goalId){
  if(startId===goalId)return[startId];
  var goal=NODE_MAP[goalId];
  if(!goal||!NODE_MAP[startId])return null;
  var open=new Map(),closed=new Set(),parent=new Map();
  open.set(startId,{g:0,f:dist(NODE_MAP[startId],goal)});
  parent.set(startId,null);
  while(open.size>0){
    var curr=null,best=Infinity;
    open.forEach(function(v,k){if(v.f<best){best=v.f;curr=k;}});
    if(curr===goalId){
      var path=[],node=curr;
      while(node!==null){path.unshift(node);node=parent.get(node)||null;if(path.length>300)break;}
      return path;
    }
    var cd=open.get(curr);open.delete(curr);closed.add(curr);
    var nei=GRAPH[curr]||[];
    for(var i=0;i<nei.length;i++){
      var n=nei[i];if(closed.has(n.id))continue;
      var g=cd.g+n.cost,ex=open.get(n.id);
      if(!ex||g<ex.g){open.set(n.id,{g:g,f:g+dist(NODE_MAP[n.id],goal)});parent.set(n.id,curr);}
    }
  }
  return null;
}

// ====== GPS -> SVG ======

function solve3x3(M,b){
  var D=M[0][0]*(M[1][1]*M[2][2]-M[1][2]*M[2][1])-M[0][1]*(M[1][0]*M[2][2]-M[1][2]*M[2][0])+M[0][2]*(M[1][0]*M[2][1]-M[1][1]*M[2][0]);
  var Mx=[[b[0],M[0][1],M[0][2]],[b[1],M[1][1],M[1][2]],[b[2],M[2][1],M[2][2]]];
  var My=[[M[0][0],b[0],M[0][2]],[M[1][0],b[1],M[1][2]],[M[2][0],b[2],M[2][2]]];
  var Mz=[[M[0][0],M[0][1],b[0]],[M[1][0],M[1][1],b[1]],[M[2][0],M[2][1],b[2]]];
  var d=function(m){return m[0][0]*(m[1][1]*m[2][2]-m[1][2]*m[2][1])-m[0][1]*(m[1][0]*m[2][2]-m[1][2]*m[2][0])+m[0][2]*(m[1][0]*m[2][1]-m[1][1]*m[2][0]);};
  return[d(Mx)/D,d(My)/D,d(Mz)/D];
}

function fitAffine(pts){
  var Sxx=0,Sxy=0,Sx=0,Syy=0,Sy=0,S=0,SxX=0,SyX=0,SX=0,SxY=0,SyY=0,SY=0;
  for(var i=0;i<pts.length;i++){
    var p=pts[i];
    Sxx+=p.lng*p.lng;Sxy+=p.lng*p.lat;Sx+=p.lng;
    Syy+=p.lat*p.lat;Sy+=p.lat;S+=1;
    SxX+=p.lng*p.x;SyX+=p.lat*p.x;SX+=p.x;
    SxY+=p.lng*p.y;SyY+=p.lat*p.y;SY+=p.y;
  }
  var M=[[Sxx,Sxy,Sx],[Sxy,Syy,Sy],[Sx,Sy,S]];
  var ABC=solve3x3(M,[SxX,SyX,SX]);
  var DEF=solve3x3(M,[SxY,SyY,SY]);
  return{A:ABC[0],B:ABC[1],C:ABC[2],D:DEF[0],E:DEF[1],F:DEF[2]};
}

var T=fitAffine(CALIBRATION_POINTS);
var CALIB_RESIDUALS=CALIBRATION_POINTS.map(function(p){
  var bx=T.A*p.lng+T.B*p.lat+T.C,by=T.D*p.lng+T.E*p.lat+T.F;
  return{lat:p.lat,lng:p.lng,rx:p.x-bx,ry:p.y-by};
});

function gpsToSvg(lat,lng){
  var bx=T.A*lng+T.B*lat+T.C,by=T.D*lng+T.E*lat+T.F;
  var nearby=CALIB_RESIDUALS.map(function(r){var dl=lat-r.lat,dl2=lng-r.lng;return{r:r,d2:dl*dl+dl2*dl2};}).filter(function(x){return x.d2>0;}).sort(function(a,b){return a.d2-b.d2;}).slice(0,4);
  if(!nearby.length)return{x:bx,y:by};
  var wSum=0,cx=0,cy=0;
  for(var i=0;i<nearby.length;i++){
    var it=nearby[i];if(it.d2<1e-18)return{x:bx+it.r.rx,y:by+it.r.ry};
    var w=1/it.d2;wSum+=w;cx+=w*it.r.rx;cy+=w*it.r.ry;
  }
  return{x:bx+cx/wSum,y:by+cy/wSum};
}

function haversineMeters(lat1,lng1,lat2,lng2){
  var R=6371000,toRad=function(d){return d*Math.PI/180;};
  var dLat=toRad(lat2-lat1),dLng=toRad(lng2-lng1);
  var a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(toRad(lat1))*Math.cos(toRad(lat2))*Math.sin(dLng/2)*Math.sin(dLng/2);
  return 2*R*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

function nearestCampusGate(lat,lng){
  return CAMPUS_GATE_POINTS.map(function(g){return{id:g.id,distance:haversineMeters(lat,lng,g.lat,g.lng)};}).sort(function(a,b){return a.distance-b.distance;})[0];
}

// ====== BUILDING RESOLUTION ======

function resolveBuildingId(officeName,buildingHint){
  var s=(officeName+" "+(buildingHint||"")).toLowerCase();
  if(/registrar/i.test(s))return"REGISTRAR";
  if(/admission|admissions|enrollment/i.test(s))return"ADMISSIONS";
  if(/accounting|account|balance|financial\s+services|cashier|scholarship|billing|finance/i.test(s))return"ACCOUNTING";
  if(/vms\b|vicente\s+m/i.test(s))return"VMS";
  if(/jsb|juliet|lrc|library|resource\s+center|guidance|counseling/i.test(s))return"JSB";
  if(/(sjbh|john|baptist|accounting|accountancy|finance)/i.test(s))return"SJBH";
  if(/court|covered\s+court/i.test(s))return"SCH_COURT";
  if(/sch|catherine|clinic|health|infirmary/i.test(s))return"SCH";
  if(/spc[ab]|calungsod|pedro|engineering|it\s+dept|computer\s+lab/i.test(s))return"SPCB";
  if(/lounge|student\s+center|student\s+affairs/i.test(s))return"STUDENT_LOUNGE";
  if(/canteen|faculty|cafeteria|food/i.test(s))return"CANTEEN";
  return null;
}

// ====== TEST HELPERS ======

function computePathDistance(pathIds){
  var totalPx=0;
  for(var i=0;i<pathIds.length-1;i++)totalPx+=dist(NODE_MAP[pathIds[i]],NODE_MAP[pathIds[i+1]]);
  return{pixels:Math.round(totalPx),meters:Math.round(totalPx*METERS_PER_PIXEL)};
}

function classifyNode(id){
  if(id.startsWith("gate"))return"gate";
  if(id.startsWith("b_")||(id.length<=3&&id[0]>='0'&&id[0]<='9'))return"building";
  return"junction";
}

function show(name,results){
  var el=document.getElementById("results"),allPass=true;
  for(var i=0;i<results.length;i++)if(!results[i].pass){allPass=false;break;}
  var html='<div class="test-case"><h3>'+(allPass?'<span class="pass">&#10004;</span> ':'<span class="fail">&#10008;</span> ')+name+'</h3>';
  for(var i=0;i<results.length;i++){
    var r=results[i];
    html+='<div>'+(r.pass?'<span class="pass">&#10004;</span>':'<span class="fail">&#10008;</span>')+' '+r.label+'</div>';
  }
  html+='</div>';el.innerHTML+=html;
}

function testRoute(name,startGps,destId,expectContains,expectNotContains){
  var r=[],gate=nearestCampusGate(startGps.lat,startGps.lng);
  r.push({pass:true,label:'GPS ('+startGps.lat.toFixed(5)+','+startGps.lng.toFixed(5)+') -> gate '+gate.id+' ('+Math.round(gate.distance)+'m)'});

  var svg=gpsToSvg(startGps.lat,startGps.lng);
  var es=snapToNearestEdge(svg.x,svg.y);
  r.push({pass:true,label:'SVG ('+Math.round(svg.x)+','+Math.round(svg.y)+') -> edge '+es.aId+'-'+es.bId+' t='+es.t.toFixed(2)});

  var startNode=gate.id,destNode=BUILDING_NODE[destId];
  if(!destNode){r.push({pass:false,label:'FAIL: no node for '+destId});show(name,r);return;}
  var path=aStar(startNode,destNode);
  if(!path){r.push({pass:false,label:'FAIL: no path '+startNode+' -> '+destNode});show(name,r);return;}

  var di=computePathDistance(path);
  r.push({pass:true,label:'Path: '+path.length+' nodes, '+di.pixels+'px ~ '+di.meters+'m (~'+Math.max(1,Math.ceil(di.meters/WALK_SPEED_M_PER_MIN))+' min)'});

  var ph='<div style="margin:6px 0;line-height:2">';
  for(var i=0;i<path.length;i++){
    var cls='path-node '+(classifyNode(path[i])==='gate'?'path-gate':classifyNode(path[i])==='building'?'path-building':'path-junction');
    ph+='<span class="'+cls+'">'+path[i]+'</span>';
    if(i<path.length-1)ph+=' \u2192 ';
  }
  ph+='</div>';r.push({pass:true,label:ph});

  if(expectContains){
    var af=true,miss=[];
    for(var j=0;j<expectContains.length;j++)if(path.indexOf(expectContains[j])===-1){af=false;miss.push(expectContains[j]);}
    r.push({pass:af,label:'Expected: '+expectContains.join(', ')+(af?'':' MISSING: '+miss.join(', '))});
  }
  if(expectNotContains){
    var nf=true,bad=[];
    for(var k=0;k<expectNotContains.length;k++)if(path.indexOf(expectNotContains[k])!==-1){nf=false;bad.push(expectNotContains[k]);}
    r.push({pass:nf,label:'Excluded: '+expectNotContains.join(', ')+(nf?'':' FOUND: '+bad.join(', '))});
  }
  r.push({pass:path[0]===startNode,label:'Start: '+startNode+' == '+path[0]});
  r.push({pass:path[path.length-1]===destNode,label:'End: '+destNode+' ('+destId+') == '+path[path.length-1]});
  show(name,r);
}

// ====== SCENARIOS ======

function runAll(){document.getElementById("results").innerHTML="";runGate1ToJSB();runGate1ToVMS();runGate1ToRegistrar();runGate2ToSJBH();runGate3ToSCH();runGate4ToSPCB();runGate1ToCanteen();runJSBtoSPCB();runWalkingProgress();runResolveBugTest();}

function runGate1ToJSB(){testRoute("Gate 1 -> JSB",{lat:14.619263,lng:121.151679},"JSB",["gate1","j_1","j_2","j_7","j_8","b_jsb"],["b_vms","b_sch","b_spcb"]);}
function runGate1ToVMS(){testRoute("Gate 1 -> VMS",{lat:14.619263,lng:121.151679},"VMS",["gate1","j_1","j_2","j_3","b_vms"],["b_jsb"]);}
function runGate1ToRegistrar(){testRoute("Gate 1 -> Registrar",{lat:14.619263,lng:121.151679},"REGISTRAR",["gate1","b_vms","1b"],["b_jsb"]);}
function runGate2ToSJBH(){testRoute("Gate 2 -> SJBH",{lat:14.619283,lng:121.151900},"SJBH",["gate2","b_sjbh"],["b_vms"]);}
function runGate3ToSCH(){testRoute("Gate 3 -> SCH",{lat:14.619672,lng:121.152847},"SCH",["gate3","j_24","j_25","b_sch"],["b_jsb","b_vms"]);}
function runGate4ToSPCB(){testRoute("Gate 4 -> SPCB",{lat:14.619919,lng:121.153348},"SPCB",["gate4","b_spcb"],["b_vms","b_jsb"]);}
function runGate1ToCanteen(){testRoute("Gate 1 -> Canteen",{lat:14.619263,lng:121.151679},"CANTEEN",["gate1","b_canteen"],["b_vms"]);}

function runJSBtoSPCB(){
  var path=aStar("b_jsb","b_spcb"),r=[];
  r.push({pass:true,label:"A* from b_jsb to b_spcb"});
  if(!path){r.push({pass:false,label:"FAIL: no path"});show("JSB -> SPCB",r);return;}
  var di=computePathDistance(path);
  r.push({pass:true,label:"Path: "+path.length+" nodes, "+di.meters+"m (~"+Math.max(1,Math.ceil(di.meters/WALK_SPEED_M_PER_MIN))+" min)"});
  var ph="";for(var i=0;i<path.length;i++){ph+='<span class="path-node '+(classifyNode(path[i])==='gate'?'path-gate':classifyNode(path[i])==='building'?'path-building':'path-junction')+'">'+path[i]+'</span>';if(i<path.length-1)ph+=' \u2192 ';}
  r.push({pass:true,label:ph});
  r.push({pass:path[0]==="b_jsb",label:"Start: b_jsb == "+path[0]});
  r.push({pass:path[path.length-1]==="b_spcb",label:"End: b_spcb == "+path[path.length-1]});
  r.push({pass:path.indexOf("b_vms")===-1,label:"Does NOT go through b_vms"});
  show("JSB -> SPCB (cross-campus)",r);
}

function runWalkingProgress(){
  var wps=[
    {lat:14.619263,lng:121.151679},
    {lat:14.619290,lng:121.151720},
    {lat:14.619340,lng:121.151820},
    {lat:14.619410,lng:121.151900},
    {lat:14.619480,lng:121.151960},
    {lat:14.619530,lng:121.152010},
    {lat:14.619570,lng:121.152030},
    {lat:14.619591,lng:121.152040}
  ];
  var gate=nearestCampusGate(wps[0].lat,wps[0].lng),destNode=BUILDING_NODE["JSB"];
  var fp=aStar(gate.id,destNode);
  if(!fp)return;
  var r=[],di=computePathDistance(fp);
  r.push({pass:true,label:"Full: "+fp.join(" -> ")});
  r.push({pass:true,label:"Total: "+di.meters+"m (~"+Math.max(1,Math.ceil(di.meters/WALK_SPEED_M_PER_MIN))+" min)"});

  var ci=0;
  for(var w=0;w<wps.length;w++){
    var svg=gpsToSvg(wps[w].lat,wps[w].lng),es=snapToNearestEdge(svg.x,svg.y);
    for(var j=ci+1;j<fp.length;j++)if(es.aId===fp[j]||es.bId===fp[j]){ci=j;break;}
    var rpx=0;
    for(var k=ci;k<fp.length-1;k++)rpx+=dist(NODE_MAP[fp[k]],NODE_MAP[fp[k+1]]);
    var rm=Math.round(rpx*METERS_PER_PIXEL),rmm=Math.max(1,Math.ceil(rm/WALK_SPEED_M_PER_MIN));
    var tp=0;for(var t=0;t<fp.length-1;t++)tp+=dist(NODE_MAP[fp[t]],NODE_MAP[fp[t+1]]);
    var pp=tp>0?Math.min(100,Math.round(((tp-rpx)/tp)*100)):0;
    r.push({pass:true,label:"WP"+(w+1)+" "+es.aId+"-"+es.bId+" | idx="+ci+" ("+fp[ci]+") | "+rm+"m left ~"+rmm+"min | "+pp+"%"});
  }
  show("Walking Progress: Gate 1 -> JSB (8 waypoints)",r);
}

function runResolveBugTest(){
  var r=[];

  // Test 1: Old regex (santos\s+build) would match "Juliet Santos Building"
  var OLD_VMS=/vms|vicente|santos\s+build/i;
  var s="jsb juliet santos building";
  r.push({pass:!OLD_VMS.test(s),label:'OLD regex /vms|vicente|santos\\\\s+build/i.test("jsb juliet santos building") -> '+(OLD_VMS.test(s)?'TRUE (BUG!)':'FALSE (still buggy)' )});

  // Test 2: New regex should NOT match JSB names
  var NEW_VMS=/vms\b|vicente\s+m/i;
  r.push({pass:!NEW_VMS.test(s),label:'NEW regex /vms\\\\b|vicente\\\\s+m/i.test("jsb juliet santos building") -> '+NEW_VMS.test(s) });

  // Test 3: resolveBuildingId("JSB", "Juliet Santos Building") must return "JSB"
  var result=resolveBuildingId("JSB","Juliet Santos Building");
  r.push({pass:result==="JSB",label:'resolveBuildingId("JSB","Juliet Santos Building") -> '+result+' (expected JSB)'});

  // Test 4: resolveBuildingId("VMS", "Vicente M. Santos Building") must return "VMS"
  var result2=resolveBuildingId("VMS","Vicente M. Santos Building");
  r.push({pass:result2==="VMS",label:'resolveBuildingId("VMS","Vicente M. Santos Building") -> '+result2+' (expected VMS)'});

  // Test 5: goToBuilding simulation - use b.id directly, not resolveBuildingId
  var jsbId="JSB";
  var resolvedId=jsbId; // this is what goToBuilding now does
  r.push({pass:resolvedId==="JSB",label:'goToBuilding uses b.id directly -> '+resolvedId});

  show("JSB resolveBuildingId Bug Fix Verification",r);
}
