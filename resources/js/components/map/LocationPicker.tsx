import { MapContainer, Marker, TileLayer, useMapEvents } from 'react-leaflet';
import InputField from '@/components/ui/InputField';
import 'leaflet/dist/leaflet.css';
import { defaultMarkerIcon } from '@/components/map/leafletIcons';

interface LocationPickerProps {
    latitude: number | null;
    longitude: number | null;
    onChange: (latitude: number, longitude: number) => void;
    error?: string;
}

// Centro aproximado de España, usado mientras no se ha marcado ninguna ubicación.
const DEFAULT_CENTER: [number, number] = [40.4168, -3.7038];

function ClickHandler({ onChange }: { onChange: (latitude: number, longitude: number) => void }) {
    useMapEvents({
        click(event) {
            onChange(event.latlng.lat, event.latlng.lng);
        },
    });

    return null;
}

export default function LocationPicker({ latitude, longitude, onChange, error }: LocationPickerProps) {
    const hasPosition = latitude !== null && longitude !== null;
    const center: [number, number] = hasPosition ? [latitude, longitude] : DEFAULT_CENTER;

    return (
        <div>
            <span className="block text-sm font-medium text-neutral-700">Ubicación exacta de la producción</span>
            <p className="mt-1 text-xs text-neutral-500">
                Haz clic en el mapa para marcarla. Es privada: solo tu empresa y los administradores la verán. A los
                compradores les mostraremos una ubicación aproximada.
            </p>

            <div className="mt-2 h-64 w-full overflow-hidden rounded-xl border border-neutral-300">
                <MapContainer center={center} zoom={hasPosition ? 13 : 6} className="h-full w-full">
                    <TileLayer
                        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                    />
                    <ClickHandler onChange={onChange} />
                    {hasPosition && <Marker position={[latitude, longitude]} icon={defaultMarkerIcon} />}
                </MapContainer>
            </div>

            {error && (
                <p className="mt-1 text-sm text-red-600" role="alert">
                    {error}
                </p>
            )}

            <div className="mt-3 grid grid-cols-2 gap-4">
                <InputField
                    label="Latitud"
                    type="number"
                    step="any"
                    name="exact_latitude"
                    value={latitude ?? ''}
                    onChange={(e) => onChange(Number(e.target.value), longitude ?? DEFAULT_CENTER[1])}
                />
                <InputField
                    label="Longitud"
                    type="number"
                    step="any"
                    name="exact_longitude"
                    value={longitude ?? ''}
                    onChange={(e) => onChange(latitude ?? DEFAULT_CENTER[0], Number(e.target.value))}
                />
            </div>
        </div>
    );
}
