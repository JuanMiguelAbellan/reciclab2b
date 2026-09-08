import InputField from '@/components/ui/InputField';
import SelectField from '@/components/ui/Select';

export interface CompanyTypeOption {
    value: string;
    label: string;
}

export interface CompanyFormData {
    trade_name: string;
    legal_name: string;
    tax_id: string;
    company_type: string;
    address: string;
    postal_code: string;
    municipality: string;
    province: string;
    autonomous_community: string;
    phone: string;
    email: string;
    website: string;
    contact_person: string;
    [key: string]: string;
}

interface CompanyFormFieldsProps {
    data: CompanyFormData;
    setData: (key: keyof CompanyFormData, value: string) => void;
    errors: Partial<Record<keyof CompanyFormData, string>>;
    companyTypes: CompanyTypeOption[];
}

export default function CompanyFormFields({ data, setData, errors, companyTypes }: CompanyFormFieldsProps) {
    return (
        <>
            <InputField
                label="Nombre comercial"
                name="trade_name"
                value={data.trade_name}
                onChange={(e) => setData('trade_name', e.target.value)}
                error={errors.trade_name}
                required
                autoFocus
            />

            <InputField
                label="Razón social"
                name="legal_name"
                value={data.legal_name}
                onChange={(e) => setData('legal_name', e.target.value)}
                error={errors.legal_name}
                required
            />

            <InputField
                label="CIF / NIF"
                name="tax_id"
                value={data.tax_id}
                onChange={(e) => setData('tax_id', e.target.value)}
                error={errors.tax_id}
                required
            />

            <SelectField
                label="Tipo de empresa"
                name="company_type"
                value={data.company_type}
                onChange={(e) => setData('company_type', e.target.value)}
                error={errors.company_type}
                options={companyTypes}
                placeholder="Selecciona un tipo"
                required
            />

            <InputField
                label="Dirección"
                name="address"
                value={data.address}
                onChange={(e) => setData('address', e.target.value)}
                error={errors.address}
            />

            <div className="grid grid-cols-2 gap-4">
                <InputField
                    label="Código postal"
                    name="postal_code"
                    value={data.postal_code}
                    onChange={(e) => setData('postal_code', e.target.value)}
                    error={errors.postal_code}
                />
                <InputField
                    label="Municipio"
                    name="municipality"
                    value={data.municipality}
                    onChange={(e) => setData('municipality', e.target.value)}
                    error={errors.municipality}
                />
            </div>

            <div className="grid grid-cols-2 gap-4">
                <InputField
                    label="Provincia"
                    name="province"
                    value={data.province}
                    onChange={(e) => setData('province', e.target.value)}
                    error={errors.province}
                />
                <InputField
                    label="Comunidad autónoma"
                    name="autonomous_community"
                    value={data.autonomous_community}
                    onChange={(e) => setData('autonomous_community', e.target.value)}
                    error={errors.autonomous_community}
                />
            </div>

            <div className="grid grid-cols-2 gap-4">
                <InputField
                    label="Teléfono"
                    name="phone"
                    value={data.phone}
                    onChange={(e) => setData('phone', e.target.value)}
                    error={errors.phone}
                />
                <InputField
                    label="Correo de la empresa"
                    type="email"
                    name="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                />
            </div>

            <div className="grid grid-cols-2 gap-4">
                <InputField
                    label="Sitio web"
                    type="url"
                    name="website"
                    value={data.website}
                    onChange={(e) => setData('website', e.target.value)}
                    error={errors.website}
                />
                <InputField
                    label="Persona de contacto"
                    name="contact_person"
                    value={data.contact_person}
                    onChange={(e) => setData('contact_person', e.target.value)}
                    error={errors.contact_person}
                />
            </div>
        </>
    );
}
