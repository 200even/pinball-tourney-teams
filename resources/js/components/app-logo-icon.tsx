import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
            {/* Background circle with cyberpunk gradient */}
            <circle cx="24" cy="24" r="22" fill="url(#cyberpunkGradient)" stroke="url(#neonStroke)" strokeWidth="2"/>
            
            {/* Pinball machine outline */}
            <rect x="12" y="8" width="24" height="32" rx="3" fill="none" stroke="currentColor" strokeWidth="2" opacity="0.8"/>
            
            {/* Pinball flippers */}
            <path d="M15 34 L21 31" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" opacity="0.9"/>
            <path d="M33 34 L27 31" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" opacity="0.9"/>
            
            {/* Pinball */}
            <circle cx="24" cy="24" r="3" fill="currentColor" opacity="0.9"/>
            
            {/* Bumpers */}
            <circle cx="18" cy="18" r="2" fill="currentColor" opacity="0.7"/>
            <circle cx="30" cy="21" r="2" fill="currentColor" opacity="0.7"/>
            <circle cx="24" cy="15" r="1.5" fill="currentColor" opacity="0.6"/>
            
            {/* Side rails */}
            <line x1="13" y1="10" x2="13" y2="38" stroke="currentColor" strokeWidth="1.5" opacity="0.5"/>
            <line x1="35" y1="10" x2="35" y2="38" stroke="currentColor" strokeWidth="1.5" opacity="0.5"/>
            
            {/* Cyberpunk gradient definitions */}
            <defs>
                <linearGradient id="cyberpunkGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stopColor="rgba(26, 0, 51, 0.1)"/>
                    <stop offset="50%" stopColor="rgba(51, 0, 102, 0.1)"/>
                    <stop offset="100%" stopColor="rgba(13, 0, 26, 0.1)"/>
                </linearGradient>
                
                <linearGradient id="neonStroke" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stopColor="currentColor" stopOpacity="0.3"/>
                    <stop offset="50%" stopColor="currentColor" stopOpacity="0.5"/>
                    <stop offset="100%" stopColor="currentColor" stopOpacity="0.3"/>
                </linearGradient>
            </defs>
        </svg>
    );
}
