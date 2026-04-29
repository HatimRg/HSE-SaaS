import React from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { Inbox, Plus } from 'lucide-react';

interface EmptyStateProps {
  title?: string;
  description?: string;
  action?: string;
  onAction?: () => void;
  icon?: React.ReactNode;
}

export function EmptyState({
  title,
  description,
  action,
  onAction,
  icon,
}: EmptyStateProps) {
  const { t } = useTranslation();

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/30 p-8 text-center"
    >
      <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
        {icon || <Inbox className="h-8 w-8 text-muted-foreground" />}
      </div>
      <h3 className="text-lg font-medium">
        {title || t('empty.title')}
      </h3>
      <p className="mt-1 max-w-sm text-sm text-muted-foreground">
        {description || t('empty.description')}
      </p>
      {action && onAction && (
        <button
          onClick={onAction}
          className="mt-4 flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary-dark"
        >
          <Plus className="h-4 w-4" />
          {action}
        </button>
      )}
    </motion.div>
  );
}
