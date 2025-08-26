import { LucideIcon } from 'lucide-react';
import * as Icons from 'lucide-react';
import { cn } from '@/lib/utils';

interface IconProps {
    iconNode?: LucideIcon | null;
    name?: string;
    className?: string;
}

export function Icon({ iconNode: IconComponent, name, className }: IconProps) {
    // If iconNode is provided, use it
    if (IconComponent) {
        return <IconComponent className={cn('h-4 w-4', className)} />;
    }
    
    // If name is provided, map it to a lucide icon
    if (name) {
        // Convert kebab-case to PascalCase for lucide icon names
        const iconName = name
            .split('-')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join('');
        
        const IconFromName = Icons[iconName as keyof typeof Icons] as LucideIcon;
        
        if (IconFromName) {
            return <IconFromName className={cn('h-4 w-4', className)} />;
        }
    }

    return null;
}
