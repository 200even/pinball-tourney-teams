import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Icon } from '@/components/ui/icon';
import AppLayout from '@/layouts/app-layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tournaments',
        href: '/tournaments',
    },
    {
        title: 'Create Tournament',
        href: '/tournaments/create',
    },
];

export default function CreateTournament() {
    const { data, setData, post, processing, errors } = useForm({
        matchplay_tournament_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        post(route('tournaments.store'), {
            onSuccess: () => {
                // Will redirect to the tournament show page
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Tournament" />
            <div className="space-y-6 max-w-2xl">
                <Heading title="Create Tournament" description="Import a tournament from Matchplay Events" />
                <Card>
                    <CardHeader>
                        <CardTitle>Import from Matchplay</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="matchplay_tournament_id">Matchplay Tournament ID</Label>
                                <Input
                                    id="matchplay_tournament_id"
                                    type="text"
                                    value={data.matchplay_tournament_id}
                                    onChange={(e) => setData('matchplay_tournament_id', e.target.value)}
                                    placeholder="e.g., 12345"
                                    required
                                />
                                <InputError message={errors.matchplay_tournament_id} />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Importing...' : 'Import Tournament'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
