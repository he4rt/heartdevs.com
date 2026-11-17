import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

type ConfirmDialogProps = {
    heading: string;
    text: string;
    confirmLabel: string;
    destructive?: boolean;
    processing?: boolean;
    onConfirm: () => void;
    onClose: () => void;
};

export function ConfirmDialog({
    heading,
    text,
    confirmLabel,
    destructive,
    processing,
    onConfirm,
    onClose,
}: ConfirmDialogProps) {
    return (
        <AlertDialog open onOpenChange={(open) => !open && onClose()}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{heading}</AlertDialogTitle>
                    <AlertDialogDescription>{text}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        variant={destructive ? 'destructive' : 'default'}
                        disabled={processing}
                        onClick={(event) => {
                            event.preventDefault();
                            onConfirm();
                        }}
                    >
                        {confirmLabel}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
