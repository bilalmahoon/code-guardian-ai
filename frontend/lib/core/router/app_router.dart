import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../data/repositories/auth_repository.dart';
import '../../presentation/auth/login_screen.dart';
import '../../presentation/auth/register_screen.dart';
import '../../presentation/home/home_screen.dart';
import '../../presentation/home/dashboard_screen.dart';
import '../../presentation/analysis/analysis_list_screen.dart';
import '../../presentation/analysis/analysis_detail_screen.dart';
import '../../presentation/organization/organization_screen.dart';
import '../../presentation/repository/repository_list_screen.dart';
import '../../presentation/project/project_list_screen.dart';
import '../../presentation/settings/settings_screen.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authStateProvider);

  return GoRouter(
    initialLocation: '/',
    debugLogDiagnostics: true,
    redirect: (context, state) {
      final isLoggedIn  = authState.value != null;
      final isAuthRoute = state.matchedLocation.startsWith('/auth');

      if (!isLoggedIn && !isAuthRoute) return '/auth/login';
      if (isLoggedIn && isAuthRoute) return '/';

      return null;
    },
    routes: [
      // Auth Routes
      GoRoute(
        path: '/auth/login',
        name: 'login',
        builder: (_, __) => const LoginScreen(),
      ),
      GoRoute(
        path: '/auth/register',
        name: 'register',
        builder: (_, __) => const RegisterScreen(),
      ),

      // Main Shell
      ShellRoute(
        builder: (context, state, child) => HomeScreen(child: child),
        routes: [
          GoRoute(
            path: '/',
            name: 'dashboard',
            builder: (_, __) => const DashboardScreen(),
          ),
          GoRoute(
            path: '/analyses',
            name: 'analyses',
            builder: (_, __) => const AnalysisListScreen(),
            routes: [
              GoRoute(
                path: ':analysisId',
                name: 'analysis-detail',
                builder: (_, state) => AnalysisDetailScreen(
                  analysisId: state.pathParameters['analysisId']!,
                ),
              ),
            ],
          ),
          GoRoute(
            path: '/repositories',
            name: 'repositories',
            builder: (_, __) => const RepositoryListScreen(),
          ),
          GoRoute(
            path: '/projects',
            name: 'projects',
            builder: (_, __) => const ProjectListScreen(),
          ),
          GoRoute(
            path: '/organization',
            name: 'organization',
            builder: (_, __) => const OrganizationScreen(),
          ),
          GoRoute(
            path: '/settings',
            name: 'settings',
            builder: (_, __) => const SettingsScreen(),
          ),
        ],
      ),
    ],
    errorBuilder: (context, state) => Scaffold(
      body: Center(child: Text('Page not found: ${state.error}')),
    ),
  );
});
