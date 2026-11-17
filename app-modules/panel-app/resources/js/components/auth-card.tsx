import type { ReactNode } from 'react';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    title: string;
    description: string;
    status?: string | null;
    footer?: ReactNode;
    children: ReactNode;
};

export function AuthCard({ title, description, status, footer, children }: Props) {
    return (
        <Card>
            <CardHeader className="text-center">
                <CardTitle className="text-xl">{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-6">
                {status && (
                    <p role="status" className="text-center text-sm font-medium text-success">
                        {status}
                    </p>
                )}
                {children}
            </CardContent>
            {footer && <CardFooter className="justify-center text-sm text-muted-foreground">{footer}</CardFooter>}
        </Card>
    );
}
