import InputField from '@/components/ui/InputField';
import SelectField from '@/components/ui/Select';
import TextareaField from '@/components/ui/Textarea';
import LocationPicker from '@/components/map/LocationPicker';

export interface OfferOption {
    value: string;
    label: string;
}

export interface OfferFormData {
    material: string;
    quantity_tons: string;
    price_per_ton: string;
    generation_year: string;
    moisture_percentage: string;
    impurities_percentage: string;
    province: string;
    municipality: string;
    description: string;
    exact_latitude: string;
    exact_longitude: string;
    [key: string]: string;
}

interface OfferFormFieldsProps {
    data: OfferFormData;
    setData: (key: keyof OfferFormData, value: string) => void;
    errors: Partial<Record<keyof OfferFormData, string>>;
    materialTypes: OfferOption[];
}

export default function OfferFormFields({ data, setData, errors, materialTypes }: OfferFormFieldsProps) {
    return (
        <>
            <SelectField
                label="Material"
                name="material"
                value={data.material}
                onChange={(e) => setData('material', e.target.value)}
                error={errors.material}
                options={materialTypes}
                placeholder="Selecciona un material"
                required
            />

            <div className="grid grid-cols-2 gap-4">
                <InputField
                    label="Cantidad (toneladas)"
                    type="number"
                    step="0.01"
                    min="0"
                    name="quantity_tons"
                    value={data.quantity_tons}
                    onChange={(e) => setData('quantity_tons', e.target.value)}
                    error={errors.quantity_tons}
                    required
                />
                <InputField
                    label="Precio (€/tonelada)"
                    type="number"
                    step="0.01"
                    min="0"
                    name="price_per_ton"
                    value={data.price_per_ton}
                    onChange={(e) => setData('price_per_ton', e.target.value)}
                    error={errors.price_per_ton}
                    required
                />
            </div>

            <InputField
                label="Año de generación"
                type="number"
                name="generation_year"
                value={data.generation_year}
                onChange={(e) => setData('generation_year', e.target.value)}
                error={errors.generation_year}
                required
            />

            <div className="grid grid-cols-2 gap-4">
                <InputField
                    label="Humedad (%)"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    name="moisture_percentage"
                    value={data.moisture_percentage}
                    onChange={(e) => setData('moisture_percentage', e.target.value)}
                    error={errors.moisture_percentage}
                />
                <InputField
                    label="Impurezas (%)"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    name="impurities_percentage"
                    value={data.impurities_percentage}
                    onChange={(e) => setData('impurities_percentage', e.target.value)}
                    error={errors.impurities_percentage}
                />
            </div>

            <div className="grid grid-cols-2 gap-4">
                <InputField
                    label="Provincia"
                    name="province"
                    value={data.province}
                    onChange={(e) => setData('province', e.target.value)}
                    error={errors.province}
                    required
                />
                <InputField
                    label="Municipio"
                    name="municipality"
                    value={data.municipality}
                    onChange={(e) => setData('municipality', e.target.value)}
                    error={errors.municipality}
                />
            </div>

            <TextareaField
                label="Descripción"
                name="description"
                rows={4}
                value={data.description}
                onChange={(e) => setData('description', e.target.value)}
                error={errors.description}
            />

            <LocationPicker
                latitude={data.exact_latitude !== '' ? Number(data.exact_latitude) : null}
                longitude={data.exact_longitude !== '' ? Number(data.exact_longitude) : null}
                onChange={(latitude, longitude) => {
                    setData('exact_latitude', String(latitude));
                    setData('exact_longitude', String(longitude));
                }}
                error={errors.exact_latitude ?? errors.exact_longitude}
            />
        </>
    );
}
