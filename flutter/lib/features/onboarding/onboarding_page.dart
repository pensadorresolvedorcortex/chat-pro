import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:go_router/go_router.dart';

import '../dashboard/dashboard_page.dart';
import '../operations/operations_page.dart';
import '../paywall/paywall_page.dart';

class OnboardingPage extends StatefulWidget {
  const OnboardingPage({super.key});

  static const routePath = '/';
  static const routeName = 'onboarding';

  @override
  State<OnboardingPage> createState() => _OnboardingPageState();
}

class _OnboardingPageState extends State<OnboardingPage> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isSubmitting = false;
  bool _obscurePassword = true;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin(BuildContext context) async {
    final messenger = ScaffoldMessenger.of(context);
    if (!(_formKey.currentState?.validate() ?? false)) {
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() => _isSubmitting = true);
    await Future<void>.delayed(const Duration(milliseconds: 750));
    if (!mounted) return;
    setState(() => _isSubmitting = false);
    context.go(DashboardPage.routePath);
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(
        const SnackBar(
          content: Text('Login demonstrativo concluído. Bem-vindo de volta!'),
        ),
      );
  }

  void _showUnavailableFeature(BuildContext context, String channel) {
    final messenger = ScaffoldMessenger.of(context);
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text('$channel estará disponível em breve.'),
        ),
      );
  }

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF6645f6), Color(0xFF1dd3c4)],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(24, 32, 24, 40),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        SvgPicture.asset(
                          'assets/images/logo_inicio.svg',
                          height: 96,
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),
                    Text(
                      'Acesse sua jornada de estudos',
                      style: textTheme.headlineSmall?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                        height: 1.2,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'Entre com sua conta da Academia da Comunicação para continuar de onde parou.',
                      style: textTheme.titleMedium?.copyWith(
                        color: Colors.white.withOpacity(0.85),
                        fontWeight: FontWeight.w500,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 32),
                    DecoratedBox(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(28),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF0c3c64).withOpacity(0.18),
                            blurRadius: 28,
                            offset: const Offset(0, 18),
                          ),
                        ],
                      ),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 24,
                          vertical: 28,
                        ),
                        child: Form(
                          key: _formKey,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Text(
                                'Entre com seu e-mail',
                                style: textTheme.titleLarge?.copyWith(
                                  color: const Color(0xFF0c3c64),
                                  fontWeight: FontWeight.w700,
                                ),
                                textAlign: TextAlign.center,
                              ),
                              const SizedBox(height: 20),
                              TextFormField(
                                controller: _emailController,
                                decoration: const InputDecoration(
                                  labelText: 'E-mail institucional',
                                  prefixIcon: Icon(Icons.alternate_email_outlined),
                                ),
                                keyboardType: TextInputType.emailAddress,
                                validator: (value) {
                                  if (value == null || value.trim().isEmpty) {
                                    return 'Informe seu e-mail';
                                  }
                                  final email = value.trim();
                                  final emailRegex = RegExp(
                                    r'^[^\s@]+@[^\s@]+\.[^\s@]+$',
                                  );
                                  if (!emailRegex.hasMatch(email)) {
                                    return 'E-mail inválido';
                                  }
                                  return null;
                                },
                              ),
                              const SizedBox(height: 16),
                              TextFormField(
                                controller: _passwordController,
                                obscureText: _obscurePassword,
                                decoration: InputDecoration(
                                  labelText: 'Senha',
                                  prefixIcon: const Icon(Icons.lock_outline),
                                  suffixIcon: IconButton(
                                    icon: Icon(
                                      _obscurePassword
                                          ? Icons.visibility_outlined
                                          : Icons.visibility_off_outlined,
                                    ),
                                    onPressed: () {
                                      setState(() {
                                        _obscurePassword = !_obscurePassword;
                                      });
                                    },
                                  ),
                                ),
                                validator: (value) {
                                  if (value == null || value.isEmpty) {
                                    return 'Informe sua senha';
                                  }
                                  if (value.length < 6) {
                                    return 'Sua senha deve ter ao menos 6 caracteres';
                                  }
                                  return null;
                                },
                              ),
                              Align(
                                alignment: Alignment.centerRight,
                                child: TextButton(
                                  onPressed: () => _showUnavailableFeature(
                                    context,
                                    'Recuperação de senha',
                                  ),
                                  child: const Text('Esqueci minha senha'),
                                ),
                              ),
                              const SizedBox(height: 4),
                              FilledButton(
                                onPressed:
                                    _isSubmitting ? null : () => _handleLogin(context),
                                style: FilledButton.styleFrom(
                                  minimumSize: const Size.fromHeight(56),
                                  backgroundColor: const Color(0xFF6645f6),
                                  foregroundColor: Colors.white,
                                  textStyle: textTheme.titleMedium?.copyWith(
                                    fontWeight: FontWeight.w700,
                                  ),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(18),
                                  ),
                                ),
                                child: _isSubmitting
                                    ? const SizedBox(
                                        height: 22,
                                        width: 22,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2.6,
                                          valueColor: AlwaysStoppedAnimation<Color>(
                                            Colors.white,
                                          ),
                                        ),
                                      )
                                    : const Text('Entrar'),
                              ),
                              const SizedBox(height: 12),
                              OutlinedButton(
                                onPressed: () => context.go(PaywallPage.routePath),
                                style: OutlinedButton.styleFrom(
                                  minimumSize: const Size.fromHeight(52),
                                  side: const BorderSide(color: Color(0xFF6645f6), width: 1.5),
                                  foregroundColor: const Color(0xFF6645f6),
                                  textStyle: textTheme.titleMedium,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(18),
                                  ),
                                ),
                                child: const Text('Conhecer os planos Pix'),
                              ),
                              const SizedBox(height: 18),
                              Row(
                                children: [
                                  Expanded(child: Divider(color: Colors.grey.shade300)),
                                  Padding(
                                    padding:
                                        const EdgeInsets.symmetric(horizontal: 12),
                                    child: Text(
                                      'ou continue com',
                                      style: textTheme.labelLarge?.copyWith(
                                        color: Colors.grey.shade500,
                                      ),
                                    ),
                                  ),
                                  Expanded(child: Divider(color: Colors.grey.shade300)),
                                ],
                              ),
                              const SizedBox(height: 16),
                              Wrap(
                                spacing: 12,
                                runSpacing: 12,
                                children: [
                                  _SocialSignInButton(
                                    label: 'Facebook',
                                    icon: Icons.facebook,
                                    backgroundColor: const Color(0xFF1877F2),
                                    foregroundColor: Colors.white,
                                    onPressed: () => _showUnavailableFeature(
                                      context,
                                      'Login com Facebook',
                                    ),
                                  ),
                                  _SocialSignInButton(
                                    label: 'Google',
                                    icon: Icons.g_translate,
                                    backgroundColor: Colors.white,
                                    foregroundColor: const Color(0xFF0c3c64),
                                    outlineColor: const Color(0xFFE0E3EB),
                                    onPressed: () => _showUnavailableFeature(
                                      context,
                                      'Login com Google',
                                    ),
                                  ),
                                  _SocialSignInButton(
                                    label: 'E-mail corporativo',
                                    icon: Icons.mail_outline,
                                    backgroundColor: const Color(0xFF1dd3c4),
                                    foregroundColor: const Color(0xFF0c3c64),
                                    onPressed: () => _showUnavailableFeature(
                                      context,
                                      'Login com e-mail mágico',
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 20),
                              TextButton(
                                onPressed: () => _showUnavailableFeature(
                                  context,
                                  'Criação de conta',
                                ),
                                child: const Text('Criar conta agora'),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 28),
                    DecoratedBox(
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.12),
                        borderRadius: BorderRadius.circular(24),
                        border: Border.all(color: Colors.white.withOpacity(0.14)),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(20),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Icon(Icons.check_circle_outline,
                                    color: Colors.white.withOpacity(0.9)),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Text(
                                    'Assinantes têm acesso a simulados express, Pix integrado e relatórios de desempenho.',
                                    style: textTheme.bodyLarge?.copyWith(
                                      color: Colors.white.withOpacity(0.88),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 16),
                            TextButton.icon(
                              onPressed: () => context.go(OperationsPage.routePath),
                              style: TextButton.styleFrom(
                                foregroundColor: Colors.white,
                              ),
                              icon: const Icon(Icons.menu_open_outlined),
                              label: const Text('Ver prontidão operacional'),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _SocialSignInButton extends StatelessWidget {
  const _SocialSignInButton({
    required this.label,
    required this.icon,
    required this.backgroundColor,
    required this.foregroundColor,
    this.onPressed,
    this.outlineColor,
  });

  final String label;
  final IconData icon;
  final Color backgroundColor;
  final Color foregroundColor;
  final VoidCallback? onPressed;
  final Color? outlineColor;

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;
    final buttonChild = Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 18),
        const SizedBox(width: 10),
        Text(
          label,
          style: textTheme.labelLarge?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );

    if (outlineColor != null) {
      return OutlinedButton(
        onPressed: onPressed,
        style: OutlinedButton.styleFrom(
          foregroundColor: foregroundColor,
          side: BorderSide(color: outlineColor!, width: 1.4),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          backgroundColor: backgroundColor,
        ),
        child: buttonChild,
      );
    }

    return FilledButton(
      onPressed: onPressed,
      style: FilledButton.styleFrom(
        foregroundColor: foregroundColor,
        backgroundColor: backgroundColor,
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
      ),
      child: buttonChild,
    );
  }
}
