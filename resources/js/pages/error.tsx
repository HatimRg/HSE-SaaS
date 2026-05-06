import { useTranslation } from 'react-i18next';
import { Link, isRouteErrorResponse, useRouteError } from 'react-router-dom';
import { AlertTriangle, ArrowLeft, RefreshCw, Shield } from 'lucide-react';

export default function ErrorPage() {
  const { t } = useTranslation();
  const error = useRouteError();
  const is404 = isRouteErrorResponse(error) && error.status === 404;
  const status = isRouteErrorResponse(error) ? error.status : 500;
  const statusText = is404 ? t('errors.notFoundTitle', 'Page Not Found') : t('errors.serverErrorTitle', 'Server Error');
  const message = is404
    ? t('errors.notFoundMessage', 'The page you are looking for does not exist or has been moved.')
    : t('errors.serverErrorMessage', 'Something went wrong on our end. Please try again later.');

  let technicalDetail = '';
  if (!is404 && error instanceof Error) {
    technicalDetail = error.message;
  } else if (isRouteErrorResponse(error) && error.data) {
    try {
      const d = typeof error.data === 'string' ? JSON.parse(error.data) : error.data;
      if (d?.exception) technicalDetail = `${d.exception}: ${d.message}`;
      if (d?.file) technicalDetail += `\n at ${d.file}:${d.line}`;
    } catch {
      technicalDetail = String(error.data).substring(0, 200);
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-background p-6">
      <div className="w-full max-w-md text-center">
        <div className="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-primary/10">
          <Shield className="h-10 w-10 text-primary" />
        </div>

        <p className="text-7xl font-black tracking-tighter text-primary/20 mb-2">{status}</p>
        <h1 className="text-2xl font-bold tracking-tight mb-2">{statusText}</h1>
        <p className="text-muted-foreground mb-8">{message}</p>

        {technicalDetail && (
          <div className="mb-6 rounded-lg border border-border bg-muted/30 p-4 text-left">
            <p className="text-xs font-semibold text-muted-foreground mb-1">{t('errors.technicalDetail', 'Technical Details')}</p>
            <pre className="text-xs text-destructive whitespace-pre-wrap break-all">{technicalDetail}</pre>
          </div>
        )}

        <div className="flex items-center justify-center gap-3">
          <Link
            to="/dashboard"
            className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity"
          >
            <ArrowLeft className="h-4 w-4" />
            {t('common:back')}
          </Link>
          <button
            onClick={() => window.location.reload()}
            className="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2.5 text-sm font-medium hover:bg-muted transition-colors"
          >
            <RefreshCw className="h-4 w-4" />
            {t('common:refresh')}
          </button>
        </div>
      </div>
    </div>
  );
}
