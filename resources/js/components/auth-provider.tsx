import React, { createContext, useContext, useEffect, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import toast from 'react-hot-toast';
import { api } from '../lib/api';

interface User {
  id: number;
  first_name: string;
  last_name: string;
  name: string; // Computed from first_name + last_name
  email: string;
  phone?: string;
  avatar?: string;
  role: {
    id: number;
    name: string;
    display_name: string;
  };
  company: {
    id: number;
    name: string;
    color_primary_light: string;
    color_primary_dark: string;
    color_background_light: string;
    color_background_dark: string;
    color_accent: string;
  };
  project_access: {
    type: 'all' | 'pole' | 'projects';
    pole_id: number | null;
    has_all_access: boolean;
    has_pole_access: boolean;
    has_specific_projects: boolean;
  };
  language: string;
  timezone: string;
  permissions: {
    is_admin: boolean;
    is_admin_like: boolean;
    is_hse: boolean;
    can_approve_kpi: boolean;
    can_approve_permit: boolean;
    can_manage_users: boolean;
    can_export: boolean;
  };
  must_change_password: boolean;
  last_login_at?: string;
}

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (email: string, password: string, remember?: boolean) => Promise<void>;
  logout: () => Promise<void>;
  updateProfile: (data: Partial<User>) => Promise<void>;
  changePassword: (currentPassword: string, newPassword: string) => Promise<void>;
  hasPermission: (permission: keyof User['permissions']) => boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const navigate = useNavigate();
  const { t, i18n } = useTranslation();
  const queryClient = useQueryClient();
  const [token, setToken] = useState<string | null>(() => 
    localStorage.getItem('auth_token')
  );

  // Fetch current user
  const { data: user, isLoading } = useQuery({
    queryKey: ['user'],
    queryFn: async () => {
      if (!token) return null;
      const response = await api.get('/user');
      return response.data.data;
    },
    enabled: !!token,
    staleTime: 1000 * 60 * 5,
  });

  // Update language when user changes
  useEffect(() => {
    if (user?.language && user.language !== i18n.language) {
      i18n.changeLanguage(user.language);
    }
  }, [user, i18n]);

  // Login mutation
  const loginMutation = useMutation({
    mutationFn: async ({ email, password, remember }: { email: string; password: string; remember?: boolean }) => {
      const response = await api.post('/login', { email, password, remember });
      return response.data.data;
    },
    onSuccess: (data) => {
      setToken(data.token);
      localStorage.setItem('auth_token', data.token);
      api.defaults.headers.Authorization = `Bearer ${data.token}`;
      queryClient.setQueryData(['user'], data.user);
      
      toast.success(t('common:success'));
      
      if (data.must_change_password) {
        navigate('/change-password');
      } else {
        navigate('/dashboard');
      }
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || t('messages:errors.unauthorized'));
    },
  });

  // Logout mutation
  const logoutMutation = useMutation({
    mutationFn: async () => {
      await api.post('/logout');
    },
    onSuccess: () => {
      setToken(null);
      localStorage.removeItem('auth_token');
      delete api.defaults.headers.Authorization;
      queryClient.clear();
      navigate('/login');
      toast.success(t('common:logout'));
    },
  });

  // Update profile mutation
  const updateProfileMutation = useMutation({
    mutationFn: async (data: Partial<User>) => {
      const response = await api.put('/user/profile', data);
      return response.data.data;
    },
    onSuccess: (data) => {
      queryClient.setQueryData(['user'], data);
      toast.success(t('common:saved'));
    },
    onError: () => {
      toast.error(t('common:error'));
    },
  });

  // Change password mutation
  const changePasswordMutation = useMutation({
    mutationFn: async ({ currentPassword, newPassword }: { currentPassword: string; newPassword: string }) => {
      await api.post('/user/change-password', {
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: newPassword,
      });
    },
    onSuccess: () => {
      toast.success(t('common:saved'));
      navigate('/dashboard');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || t('common:error'));
    },
  });

  // Set token on mount
  useEffect(() => {
    if (token) {
      api.defaults.headers.Authorization = `Bearer ${token}`;
    }
  }, [token]);

  const login = async (email: string, password: string, remember?: boolean) => {
    await loginMutation.mutateAsync({ email, password, remember });
  };

  const logout = async () => {
    await logoutMutation.mutateAsync();
  };

  const updateProfile = async (data: Partial<User>) => {
    await updateProfileMutation.mutateAsync(data);
  };

  const changePassword = async (currentPassword: string, newPassword: string) => {
    await changePasswordMutation.mutateAsync({ currentPassword, newPassword });
  };

  const hasPermission = (permission: keyof User['permissions']) => {
    return user?.permissions?.[permission] ?? false;
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoading,
        isAuthenticated: !!user && !!token,
        login,
        logout,
        updateProfile,
        changePassword,
        hasPermission,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}
