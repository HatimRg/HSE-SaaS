import React, { useState, useEffect } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { motion, AnimatePresence } from 'framer-motion';
import {
  LayoutDashboard,
  BarChart3,
  AlertTriangle,
  FileCheck,
  ClipboardCheck,
  Users,
  GraduationCap,
  HardHat,
  Leaf,
  MessageSquare,
  FolderOpen,
  Settings,
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  Shield,
  Menu,
  X,
} from 'lucide-react';
import { useAuth } from './auth-provider';

interface NavItem {
  path: string;
  label: string;
  icon: React.ElementType;
  children?: NavItem[];
  permission?: keyof ReturnType<typeof useAuth>['user']['permissions'];
}

const navItems: NavItem[] = [
  { path: '/dashboard', label: 'navigation.dashboard', icon: LayoutDashboard },
  { path: '/kpi', label: 'navigation.kpi', icon: BarChart3 },
  { path: '/sor', label: 'navigation.sor', icon: AlertTriangle },
  { path: '/permits', label: 'navigation.permits', icon: FileCheck },
  { path: '/inspections', label: 'navigation.inspections', icon: ClipboardCheck },
  { path: '/workers', label: 'navigation.workers', icon: Users },
  { path: '/training', label: 'navigation.training', icon: GraduationCap },
  { path: '/ppe', label: 'navigation.ppe', icon: HardHat },
  { path: '/environment', label: 'navigation.environment', icon: Leaf },
  { path: '/community', label: 'navigation.community', icon: MessageSquare },
  { path: '/library', label: 'navigation.library', icon: FolderOpen },
];

export function Sidebar() {
  const { t } = useTranslation();
  const { user, hasPermission } = useAuth();
  const location = useLocation();
  const [isCollapsed, setIsCollapsed] = useState(false);
  const [isMobileOpen, setIsMobileOpen] = useState(false);
  const [expandedItems, setExpandedItems] = useState<string[]>([]);

  // Handle responsive behavior
  useEffect(() => {
    const checkMobile = () => {
      if (window.innerWidth >= 1024) {
        setIsMobileOpen(false);
      }
    };
    
    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  const toggleExpanded = (path: string) => {
    setExpandedItems((prev) =>
      prev.includes(path) ? prev.filter((p) => p !== path) : [...prev, path]
    );
  };

  const isActive = (path: string) => location.pathname.startsWith(path);

  // Mobile toggle button
  const MobileToggle = () => (
    <button
      onClick={() => setIsMobileOpen(true)}
      className="lg:hidden fixed top-4 left-4 z-40 flex h-10 w-10 items-center justify-center rounded-lg bg-card border border-border shadow-md"
      aria-label="Open menu"
    >
      <Menu className="h-5 w-5" />
    </button>
  );

  // Desktop Sidebar
  const DesktopSidebar = () => (
    <motion.aside
      initial={{ width: 280 }}
      animate={{ width: isCollapsed ? 72 : 280 }}
      transition={{ type: 'spring', stiffness: 300, damping: 30 }}
      className="hidden lg:flex flex-col border-r border-border bg-card h-screen sticky top-0"
    >
      <SidebarContent />
    </motion.aside>
  );

  // Mobile Drawer
  const MobileDrawer = () => (
    <AnimatePresence>
      {isMobileOpen && (
        <>
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={() => setIsMobileOpen(false)}
            className="lg:hidden fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
          />
          {/* Drawer */}
          <motion.aside
            initial={{ x: '-100%' }}
            animate={{ x: 0 }}
            exit={{ x: '-100%' }}
            transition={{ type: 'spring', damping: 25, stiffness: 200 }}
            className="lg:hidden fixed top-0 left-0 z-50 h-full w-[280px] flex flex-col border-r border-border bg-card shadow-2xl"
          >
            {/* Close button */}
            <div className="absolute top-4 right-4">
              <button
                onClick={() => setIsMobileOpen(false)}
                className="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted transition-colors"
              >
                <X className="h-5 w-5" />
              </button>
            </div>
            <SidebarContent isMobile />
          </motion.aside>
        </>
      )}
    </AnimatePresence>
  );

  // Sidebar content component
  const SidebarContent = ({ isMobile = false }: { isMobile?: boolean }) => (
    <>
      {/* Logo */}
      <div className={`flex h-16 items-center justify-between border-b border-border px-4 ${isMobile ? 'pr-12' : ''}`}>
        <AnimatePresence mode="wait">
          {(!isCollapsed || isMobile) && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="flex items-center gap-2"
            >
              <motion.div 
                className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
              >
                <Shield className="h-5 w-5" />
              </motion.div>
              <span className="font-semibold text-lg">{t('common.appName')}</span>
            </motion.div>
          )}
        </AnimatePresence>
        
        {!isMobile && (
          <button
            onClick={() => setIsCollapsed(!isCollapsed)}
            className="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted transition-colors"
          >
            <motion.div
              animate={{ rotate: isCollapsed ? 180 : 0 }}
              transition={{ duration: 0.2 }}
            >
              <ChevronLeft className="h-5 w-5" />
            </motion.div>
          </button>
        )}
      </div>

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto py-4 scrollbar-thin">
        <ul className="space-y-1 px-2">
          {navItems.map((item, index) => {
            const Icon = item.icon;
            const active = isActive(item.path);
            const hasChildren = item.children && item.children.length > 0;
            const isExpanded = expandedItems.includes(item.path);

            if (item.permission && !hasPermission(item.permission)) {
              return null;
            }

            return (
              <motion.li 
                key={item.path}
                initial={{ opacity: 0, x: -20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: index * 0.05 }}
              >
                {hasChildren ? (
                  <div>
                    <button
                      onClick={() => toggleExpanded(item.path)}
                      className={`w-full flex items-center gap-3 rounded-lg px-3 py-2 transition-all ${
                        active
                          ? 'bg-primary/10 text-primary'
                          : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                      }`}
                    >
                      <motion.div whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.9 }}>
                        <Icon className="h-5 w-5 flex-shrink-0" />
                      </motion.div>
                      {(!isCollapsed || isMobile) && (
                        <>
                          <span className="flex-1 text-sm font-medium text-left">
                            {t(item.label)}
                          </span>
                          <motion.div
                            animate={{ rotate: isExpanded ? 180 : 0 }}
                            transition={{ duration: 0.2 }}
                          >
                            <ChevronDown className="h-4 w-4" />
                          </motion.div>
                        </>
                      )}
                    </button>
                    
                    <AnimatePresence>
                      {isExpanded && (!isCollapsed || isMobile) && (
                        <motion.ul
                          initial={{ height: 0, opacity: 0 }}
                          animate={{ height: 'auto', opacity: 1 }}
                          exit={{ height: 0, opacity: 0 }}
                          transition={{ duration: 0.2 }}
                          className="ml-4 mt-1 space-y-1 overflow-hidden"
                        >
                          {item.children.map((child, childIndex) => (
                            <motion.li 
                              key={child.path}
                              initial={{ opacity: 0, x: -10 }}
                              animate={{ opacity: 1, x: 0 }}
                              transition={{ delay: childIndex * 0.03 }}
                            >
                              <NavLink
                                to={child.path}
                                onClick={() => isMobile && setIsMobileOpen(false)}
                                className={({ isActive }) =>
                                  `flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all ${
                                    isActive
                                      ? 'bg-primary/10 text-primary'
                                      : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                  }`
                                }
                              >
                                <child.icon className="h-4 w-4" />
                                <span>{t(child.label)}</span>
                              </NavLink>
                            </motion.li>
                          ))}
                        </motion.ul>
                      )}
                    </AnimatePresence>
                  </div>
                ) : (
                  <NavLink
                    to={item.path}
                    onClick={() => isMobile && setIsMobileOpen(false)}
                    className={({ isActive }) =>
                      `group flex items-center gap-3 rounded-lg px-3 py-2 transition-all ${
                        isActive
                          ? 'bg-primary/10 text-primary'
                          : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                      }`
                    }
                    title={isCollapsed && !isMobile ? t(item.label) : undefined}
                  >
                    <motion.div 
                      whileHover={{ scale: 1.1, rotate: 5 }} 
                      whileTap={{ scale: 0.9 }}
                      className="relative"
                    >
                      <Icon className="h-5 w-5 flex-shrink-0" />
                      {active && isCollapsed && !isMobile && (
                        <motion.span
                          layoutId="miniIndicator"
                          className="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-primary"
                        />
                      )}
                    </motion.div>
                    {(!isCollapsed || isMobile) && (
                      <span className="text-sm font-medium">{t(item.label)}</span>
                    )}
                    {active && (!isCollapsed || isMobile) && (
                      <motion.div
                        layoutId={isMobile ? "mobileActiveIndicator" : "activeIndicator"}
                        className="ml-auto h-1.5 w-1.5 rounded-full bg-primary"
                        transition={{ type: "spring", stiffness: 300, damping: 30 }}
                      />
                    )}
                  </NavLink>
                )}
              </motion.li>
            );
          })}
        </ul>
      </nav>

      {/* Bottom section */}
      <div className="border-t border-border p-4">
        <NavLink
          to="/settings"
          onClick={() => isMobile && setIsMobileOpen(false)}
          className={({ isActive }) =>
            `group flex items-center gap-3 rounded-lg px-3 py-2 transition-all ${
              isActive
                ? 'bg-primary/10 text-primary'
                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
            }`
          }
          title={isCollapsed && !isMobile ? t('navigation.settings') : undefined}
        >
          <motion.div whileHover={{ scale: 1.1, rotate: 30 }} whileTap={{ scale: 0.9 }}>
            <Settings className="h-5 w-5" />
          </motion.div>
          {(!isCollapsed || isMobile) && (
            <span className="text-sm font-medium">{t('navigation.settings')}</span>
          )}
        </NavLink>
      </div>
    </>
  );

  return (
    <>
      <MobileToggle />
      <DesktopSidebar />
      <MobileDrawer />
    </>
  );
}
