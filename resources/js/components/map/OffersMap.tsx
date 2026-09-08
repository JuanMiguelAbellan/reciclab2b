import { MapContainer, Marker, Popup, TileLayer, useMap } from 'react-leaflet';
import { Link } from '@inertiajs/react';
import { useEffect } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { defaultMarkerIcon } from '@/components/map/leafletIcons';

interface MapOffer {
    id: number;
    material: { label: string };
    quantity_tons: number;
    price_per_ton: number;
    province: string;
    municipality: string | null;
    company_name: string;
    public_latitude: number | null;
    public_longitude: number | null;
}

interface OffersMapProps {
    offers: MapOffer[];
}

// Centro aproximado de España, usado cuando no hay ninguna oferta geolocalizada que enmarcar.
const SPAIN_CENTER: [number, number] = [40.4168, -3.7038];

function FitBounds({ positions }: { positions: [number, number][] }) {
    const map = useMap();

    useEffect(() => {
        if (positions.length === 0) {
            return;
        }

        if (positions.length === 1) {
            map.setView(positions[0], 10);
            return;
        }

        map.fitBounds(L.latLngBounds(positions), { padding: [30, 30] });
    }, [positions, map]);

    return null;
}

export default function OffersMap({ offers }: OffersMapProps) {
    const located = offers.filter(
        (offer): offer is MapOffer & { public_latitude: number; public_longitude: number } =>
            offer.public_latitude !== null && offer.public_longitude !== null,
    );

    const positions: [number, number][] = located.map((offer) => [offer.public_latitude, offer.public_longitude]);

    return (
        <div className="mt-6 h-80 w-full overflow-hidden rounded-xl border border-neutral-200">
            <MapContainer center={SPAIN_CENTER} zoom={6} className="h-full w-full">
                <TileLayer
                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                />
                <FitBounds positions={positions} />
                {located.map((offer) => (
                    <Marker
                        key={offer.id}
                        position={[offer.public_latitude, offer.public_longitude]}
                        icon={defaultMarkerIcon}
                    >
                        <Popup>
                            <p className="font-semibold">{offer.material.label}</p>
                            <p>
                                {offer.quantity_tons} t &middot; {offer.price_per_ton} €/t
                            </p>
                            <p>
                                {offer.municipality ? `${offer.municipality}, ` : ''}
                                {offer.province}
                            </p>
                            <Link href={`/mercado/${offer.id}`} className="text-neutral-900 underline">
                                Ver ficha
                            </Link>
                        </Popup>
                    </Marker>
                ))}
            </MapContainer>
        </div>
    );
}
