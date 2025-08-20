import { Icon } from '@/components/ui/icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-gradient-to-br from-blue-500 to-purple-600 text-white">
                <Icon name="zap" className="size-5" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">Pinball Teams</span>
                <span className="truncate text-xs text-muted-foreground">Tournament Tracker</span>
            </div>
        </>
    );
}
