import { PATH_A, PATH_B } from '../He4rtLogo';

export function PubBrand() {
    return (
        <div className="absolute left-8 top-[26px] flex -rotate-3 items-center gap-[14px]">
            <span className="grid h-[58px] w-[58px] place-items-center rounded-full bg-pub-purple shadow-[0_6px_20px_rgba(0,0,0,.45)]">
                <svg viewBox="-24 -16 632 548" className="h-[38px] w-[38px]" aria-hidden="true">
                    <path d={PATH_A} fill="#fff" />
                    <path d={PATH_B} fill="#fff" />
                </svg>
            </span>
            <span className="font-pub-marker text-[46px] leading-none text-white [text-shadow:0_3px_14px_rgba(0,0,0,.6)]">
                He4rt<span className="text-pub-yellow">Pub</span>
            </span>
        </div>
    );
}

export default PubBrand;
