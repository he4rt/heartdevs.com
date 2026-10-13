import type { StreamEventDto } from '../feed';
import type { EventAlertProps } from '../ui/footer/EventAlert';

export function toEventAlert(e: StreamEventDto): EventAlertProps | null {
    switch (e.type) {
        case 'follow':
            return {
                icon: '⭐',
                accent: '#c9a4ff',
                title: 'NOVO FOLLOW',
                name: '@' + e.username,
                detail: '',
            };
        case 'sub':
            return {
                icon: '💜',
                accent: '#8b2fe8',
                title: 'NOVO SUB',
                name: '@' + e.username,
                detail: e.months > 1 ? e.months + ' meses' : '',
            };
        case 'donation':
            return {
                icon: '💸',
                accent: '#1ed760',
                title: 'DOAÇÃO',
                name: '@' + e.username,
                detail: 'R$ ' + (e.amountCents / 100).toFixed(2),
            };
        case 'giftSub':
            return {
                icon: '🎁',
                accent: '#8b2fe8',
                title: 'GIFT SUB',
                name: '@' + e.username,
                detail: e.total + ' subs',
            };
        case 'cheer':
            return {
                icon: '💎',
                accent: '#ffcb05',
                title: 'BITS',
                name: '@' + e.username,
                detail: e.bits + ' bits',
            };
        case 'raid':
            return {
                icon: '⚡',
                accent: '#ffcb05',
                title: 'RAID',
                name: '@' + e.fromChannel,
                detail: '+' + e.viewers + ' viewers',
            };
        case 'viewerCountUpdate':
            return null;
    }
}
