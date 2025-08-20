import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { Icon } from '@/components/ui/icon';
import { Link, usePage } from '@inertiajs/react';

const tournamentNavItems = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: 'home',
    },
    {
        title: 'Tournaments',
        href: '/tournaments',
        icon: 'trophy',
    },
    {
        title: 'Settings',
        href: '/settings/profile',
        icon: 'settings',
    },
];

export function NavMain() {
    const page = usePage();
    
    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Tournament Management</SidebarGroupLabel>
            <SidebarMenu>
                {tournamentNavItems.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton asChild isActive={page.url.startsWith(item.href)} tooltip={{ children: item.title }}>
                            <Link href={item.href} prefetch>
                                <Icon name={item.icon as any} className="h-4 w-4" />
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
