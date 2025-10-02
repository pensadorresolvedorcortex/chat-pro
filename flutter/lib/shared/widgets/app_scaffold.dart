import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:go_router/go_router.dart';

import '../../features/dashboard/dashboard_page.dart';
import '../../features/operations/operations_page.dart';
import '../../features/paywall/paywall_page.dart';
import '../../features/questions/questions_page.dart';
import '../../features/simulados/simulados_page.dart';

class AppScaffold extends StatelessWidget {
  const AppScaffold({
    required this.body,
    super.key,
    this.actions,
    this.floatingActionButton,
    this.includeDrawer = true,
    this.backgroundColor,
  });

  final Widget body;
  final List<Widget>? actions;
  final Widget? floatingActionButton;
  final bool includeDrawer;
  final Color? backgroundColor;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      backgroundColor: backgroundColor ?? theme.scaffoldBackgroundColor,
      appBar: AppBar(
        automaticallyImplyLeading: includeDrawer,
        title: SvgPicture.asset(
          'assets/images/logo_header.svg',
          height: 32,
        ),
        actions: actions,
      ),
      drawer: includeDrawer ? const AppNavigationDrawer() : null,
      body: body,
      floatingActionButton: floatingActionButton,
    );
  }
}

class AppNavigationDrawer extends StatelessWidget {
  const AppNavigationDrawer({super.key});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final location = GoRouter.of(context).location;
    final entries = <_DrawerEntry>[
      _DrawerEntry(
        icon: Icons.dashboard_outlined,
        label: 'Dashboard',
        route: DashboardPage.routePath,
      ),
      _DrawerEntry(
        icon: Icons.quiz_outlined,
        label: 'Questões',
        route: QuestionsPage.routePath,
      ),
      _DrawerEntry(
        icon: Icons.assignment_turned_in_outlined,
        label: 'Simulados',
        route: SimuladosPage.routePath,
      ),
      _DrawerEntry(
        icon: Icons.payments_outlined,
        label: 'Assinaturas Pix',
        route: PaywallPage.routePath,
      ),
      _DrawerEntry(
        icon: Icons.monitor_heart_outlined,
        label: 'Status Operacional',
        route: OperationsPage.routePath,
      ),
      _DrawerEntry(
        icon: Icons.video_library_outlined,
        label: 'Biblioteca',
        route: '/biblioteca',
      ),
      _DrawerEntry(
        icon: Icons.people_alt_outlined,
        label: 'Mentorias',
        route: '/mentorias',
      ),
    ];

    return Drawer(
      child: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            DrawerHeader(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFF6645f6), Color(0xFF1dd3c4)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: Align(
                alignment: Alignment.bottomLeft,
                child: SvgPicture.asset(
                  'assets/images/logo_inicio.svg',
                  height: 56,
                ),
              ),
            ),
            Expanded(
              child: ListView.builder(
                itemCount: entries.length,
                itemBuilder: (context, index) {
                  final entry = entries[index];
                  final isSelected =
                      location == entry.route || location.startsWith('${entry.route}/');
                  return ListTile(
                    leading: Icon(
                      entry.icon,
                      color: isSelected ? colorScheme.primary : null,
                    ),
                    title: Text(
                      entry.label,
                      style: TextStyle(
                        fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                      ),
                    ),
                    selected: isSelected,
                    onTap: () {
                      Navigator.of(context).pop();
                      context.go(entry.route);
                    },
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DrawerEntry {
  const _DrawerEntry({
    required this.icon,
    required this.label,
    required this.route,
  });

  final IconData icon;
  final String label;
  final String route;
}
