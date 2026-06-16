import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import 'package:responsive_framework/responsive_framework.dart';

import '../../core/theme/app_theme.dart';
import '../../data/repositories/auth_repository.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isMobile = ResponsiveBreakpoints.of(context).isMobile;

    if (isMobile) {
      return _MobileScaffold(child: child);
    }

    return _DesktopScaffold(child: child);
  }
}

class _DesktopScaffold extends ConsumerWidget {
  const _DesktopScaffold({required this.child});

  final Widget child;

  static const _navItems = [
    _NavItem(icon: Icons.dashboard_outlined, label: 'Dashboard', route: '/'),
    _NavItem(icon: Icons.analytics_outlined, label: 'Analyses', route: '/analyses'),
    _NavItem(icon: Icons.account_tree_outlined, label: 'Projects', route: '/projects'),
    _NavItem(icon: Icons.storage_outlined, label: 'Repositories', route: '/repositories'),
    _NavItem(icon: Icons.people_outlined, label: 'Organization', route: '/organization'),
    _NavItem(icon: Icons.settings_outlined, label: 'Settings', route: '/settings'),
  ];

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final location = GoRouterState.of(context).matchedLocation;

    return Scaffold(
      body: Row(
        children: [
          // Sidebar navigation
          NavigationDrawer(
            onDestinationSelected: (index) {
              context.go(_navItems[index].route);
            },
            selectedIndex: _navItems.indexWhere(
              (item) => location.startsWith(item.route) &&
                  (item.route == '/' ? location == '/' : true),
            ),
            children: [
              // Logo
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 8),
                child: Row(
                  children: [
                    Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        color: AppColors.primary,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(Icons.shield_outlined,
                          color: Colors.white, size: 20),
                    ),
                    const SizedBox(width: 10),
                    Text(
                      'CodeGuardian',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                  ],
                ),
              ),
              const Divider(height: 24),
              ..._navItems.map((item) => NavigationDrawerDestination(
                    icon: Icon(item.icon),
                    label: Text(item.label),
                  )),
              const Divider(),
              // Logout
              ListTile(
                leading: const Icon(Icons.logout),
                title: const Text('Sign out'),
                onTap: () async {
                  await ref.read(authRepositoryProvider).logout();
                  ref.invalidate(authStateProvider);
                  if (context.mounted) context.go('/auth/login');
                },
              ),
            ],
          ),
          // Main content
          Expanded(child: child),
        ],
      ),
    );
  }
}

class _MobileScaffold extends StatelessWidget {
  const _MobileScaffold({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: child,
      bottomNavigationBar: NavigationBar(
        destinations: const [
          NavigationDestination(
              icon: Icon(Icons.dashboard_outlined), label: 'Dashboard'),
          NavigationDestination(
              icon: Icon(Icons.analytics_outlined), label: 'Analyses'),
          NavigationDestination(
              icon: Icon(Icons.account_tree_outlined), label: 'Projects'),
          NavigationDestination(
              icon: Icon(Icons.people_outlined), label: 'Org'),
        ],
        onDestinationSelected: (index) {
          const routes = ['/', '/analyses', '/projects', '/organization'];
          context.go(routes[index]);
        },
      ),
    );
  }
}

class _NavItem {
  const _NavItem({
    required this.icon,
    required this.label,
    required this.route,
  });

  final IconData icon;
  final String label;
  final String route;
}
