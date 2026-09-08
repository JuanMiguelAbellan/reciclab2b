import L from 'leaflet';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Vite bundles Leaflet's marker images under a hashed URL, but Leaflet
// builds its *default* icon's URLs eagerly from the unbundled relative
// paths the moment the library loads — before mergeOptions on the
// prototype has a chance to run. Passing this icon explicitly to every
// <Marker> sidesteps that timing issue instead of fighting it.
export const defaultMarkerIcon = new L.Icon({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIcon2x,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
});
