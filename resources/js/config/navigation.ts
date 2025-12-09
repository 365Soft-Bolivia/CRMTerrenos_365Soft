import { Folder, MapPinned, MapPin, Tag, LockKeyhole, Users, Briefcase, MessageSquare, Settings2 } from 'lucide-vue-next';
import { dashboard, proyectos, terrenos, categorias } from '@/routes';
import type { NavItem } from '@/types';

export const allMainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: dashboard().url,
    icon: MapPinned,
  },
  {
    title: 'Proyectos',
    href: proyectos().url,
    icon: Folder,
    roles: ['admin'],
  },
  {
    title: 'Terrenos',
    href: terrenos().url,
    icon: MapPin,
    roles: ['admin'],
  },
  {
    title: 'Categorías',
    href: categorias().url,
    icon: Tag,
    roles: ['admin'],
  },
  {
    title: 'Accesos',
    href: '/accesos',
    icon: LockKeyhole,
    roles: ['admin'],
  },
];

// Nuevo: Menú de Leads con submenús
export const leadsNavItems: NavItem[] = [
  {
    title: 'Contacto de Leads',
    href: '/leads',
    icon: Users,
  },
  {
    title: 'Negocios',
    href: '/negocios',
    icon: Briefcase,
  },
  {
    title: 'Configurar Embudos',
    href: '/negocios/embudos',
    icon: Settings2,
    roles: ['admin'],
  },
  {
    title: 'WhatsApp',
    href: '/whatsapp',
    icon: MessageSquare,
  },
];