import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import TeamManagement from '@/components/team-management';

interface Player {
    id: number;
    name: string;
    matchplay_player_id: string;
    ifpa_id?: string;
}

interface Team {
    id: number;
    name: string;
    generated_name: string;
    total_points: number;
    games_played: number;
    player1: Player;
    player2: Player;
}

interface Tournament {
    id: number;
    name: string;
    teams: Team[];
}

interface PageProps {
    tournament: Tournament;
    availablePlayers: Player[];
}

export default function TeamsIndex({ tournament, availablePlayers }: PageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tournaments',
            href: '/tournaments',
        },
        {
            title: tournament.name,
            href: `/tournaments/${tournament.id}`,
        },
        {
            title: 'Teams',
            href: `/tournaments/${tournament.id}/teams`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Teams - ${tournament.name}`} />
            
            <div className="p-4 md:p-6">
                <TeamManagement 
                    tournament={tournament}
                    availablePlayers={availablePlayers}
                />
            </div>
        </AppLayout>
    );
}
