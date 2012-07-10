<html>
	<head>
		<title>Route map</title>
		<script src="http://maps.google.com/maps?file=api&amp;v=2<? if ($config['gMapsKey']) { ?>&amp;key=ABQIAAAAFFDAmn3ZJ6l6touLHqHU2xSyordhPYUxkmXD_TGOneu-aIw4uxSktGb-0lAULA_n0El-Ul5iVCIVsQ<? } ?>" type="text/javascript"></script>
	</head>
	<body onload="loadMap()" onUnload="GUnload()">
<?php

// key for test server ABQIAAAAFFDAmn3ZJ6l6touLHqHU2xSd1SAHxGu13dYRKAHcbURoUvN5-RRw7L9D0wDieA2g6ejDX1sFgnWNQA
// key for localmotive.ca ABQIAAAAFFDAmn3ZJ6l6touLHqHU2xSyordhPYUxkmXD_TGOneu-aIw4uxSktGb-0lAULA_n0El-Ul5iVCIVsQ
?>
<div id="routeMap" style="padding: 0; margin: 0; width: 780px; height: 580px; border: 1px solid #000000;"></div>
	<script type="text/javascript">
 //<![CDATA[

function loadMap () {
	if (GBrowserIsCompatible()) {
		routesOverlay = new GGeoXml ('http://localmotive.ca/market/routes.kml');
		var map = new GMap2(document.getElementById("routeMap"));
		map.setCenter(new GLatLng(49.3, -119.53), 9);
		map.enableContinuousZoom();
		map.enableScrollWheelZoom();
		map.addControl(new GLargeMapControl());
		map.addControl(new GLargeMapControl());
		map.addOverlay(routesOverlay);
		/* GEvent.addListener(map, "click", function (route, point) {
			alert("You clicked the map on route " + route);
		}); */
	}
}

//]]>
</script>
	</body>
</html>
