import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Icon } from '@/components/ui/icon';
import { Card, CardContent } from '@/components/ui/card';

interface Props {
    url: string;
    title: string;
    onClose: () => void;
}

export default function QRCodeDisplay({ url, title, onClose }: Props) {
    const [qrCodeUrl, setQrCodeUrl] = useState<string>('');
    const [copied, setCopied] = useState(false);

    useEffect(() => {
        // Generate QR code using a service like qr-server.com with a transparent background
        const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&format=png&bgcolor=FFFFFF&color=000000&data=${encodeURIComponent(url)}`;
        setQrCodeUrl(qrApiUrl);
    }, [url]);

    const copyToClipboard = async () => {
        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
            console.error('Failed to copy URL:', err);
        }
    };

    const downloadQRCode = () => {
        const link = document.createElement('a');
        link.href = qrCodeUrl;
        link.download = `${title.replace(/\s+/g, '-').toLowerCase()}-qr-code.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    return (
        <Dialog open={true} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader className="text-center">
                    <DialogTitle>QR Code for Leaderboard</DialogTitle>
                    <DialogDescription>
                        Players can scan this code to view the live leaderboard on their devices
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-6">
                    {/* QR Code */}
                    <div className="flex justify-center">
                        <div className="bg-white dark:bg-gray-100 p-4 rounded-xl border shadow-sm">
                            {qrCodeUrl ? (
                                <img 
                                    src={qrCodeUrl} 
                                    alt="QR Code for tournament leaderboard"
                                    className="w-72 h-72 rounded-lg"
                                />
                            ) : (
                                <div className="w-72 h-72 rounded-lg flex items-center justify-center bg-gray-50">
                                    <Icon name="loader-2" className="h-8 w-8 animate-spin text-gray-400" />
                                </div>
                            )}
                        </div>
                    </div>

                    {/* URL Display */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Direct Link:</label>
                        <div className="flex items-center gap-2">
                            <div className="flex-1 p-2 bg-muted rounded text-sm font-mono truncate">
                                {url}
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={copyToClipboard}
                                className="gap-2"
                            >
                                {copied ? (
                                    <>
                                        <Icon name="check" className="h-4 w-4" />
                                        Copied!
                                    </>
                                ) : (
                                    <>
                                        <Icon name="copy" className="h-4 w-4" />
                                        Copy
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>

                    {/* Instructions */}
                    <div className="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-950 dark:to-cyan-950 border border-blue-200 dark:border-blue-800 rounded-xl p-5">
                        <div className="flex items-start gap-3">
                            <div className="bg-blue-100 dark:bg-blue-900 p-2 rounded-lg">
                                <Icon name="info" className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div className="text-sm">
                                <h4 className="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                                    How to use this QR code:
                                </h4>
                                <ul className="space-y-2 text-blue-800 dark:text-blue-200">
                                    <li className="flex items-center gap-2">
                                        <Icon name="eye" className="h-4 w-4 text-blue-500" />
                                        Display this QR code where players can see it
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Icon name="smartphone" className="h-4 w-4 text-blue-500" />
                                        Players scan with their phone camera or QR app
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Icon name="external-link" className="h-4 w-4 text-blue-500" />
                                        They'll be taken directly to the live leaderboard
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Icon name="refresh-cw" className="h-4 w-4 text-blue-500" />
                                        The leaderboard updates automatically as you sync data
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex items-center gap-3 pt-2 border-t border-border/50">
                        <Button
                            onClick={downloadQRCode}
                            disabled={!qrCodeUrl}
                            className="flex-1 gap-2 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700"
                        >
                            <Icon name="download" className="h-4 w-4" />
                            Download QR Code
                        </Button>
                        <Button
                            variant="outline"
                            onClick={onClose}
                            className="px-6"
                        >
                            Close
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
