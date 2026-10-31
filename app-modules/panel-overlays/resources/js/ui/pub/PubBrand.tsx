import logoUrl from '../../../images/he4rt-pub-logo.png';

export function PubBrand() {
    return (
        <img
            src={logoUrl}
            alt="He4rt Pub"
            className="absolute top-[22px] left-8 w-[336px] drop-shadow-[0_3px_12px_rgba(0,0,0,.55)]"
        />
    );
}

export default PubBrand;
