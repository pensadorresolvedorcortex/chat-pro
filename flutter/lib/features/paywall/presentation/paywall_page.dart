import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../shared/widgets/primary_button.dart';
import '../data/pix_charge_repository.dart';
import '../data/pix_checkout_repository.dart';
import '../data/plan_repository.dart';
import '../domain/pix_charge.dart';
import '../domain/plan.dart';
import 'widgets/plan_card.dart';

class PaywallPage extends ConsumerWidget {
  const PaywallPage({super.key});

  static const routePath = '/planos';
  static const routeName = 'paywall';

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final plansAsync = ref.watch(plansProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Planos e Assinaturas'),
      ),
      body: plansAsync.when(
        data: (result) {
          final plans = result.plans;
          final isFallback = result.isFallback;
          final fallbackReason = result.fallbackReason;

          if (plans.isEmpty) {
            return _EmptyPlansView(wasFallback: isFallback);
          }

          return RefreshIndicator(
            onRefresh: () async {
              await ref.refresh(plansProvider.future);
            },
            child: ListView(
              padding: const EdgeInsets.all(24),
              physics: const AlwaysScrollableScrollPhysics(),
              children: [
                if (isFallback)
                  _PlanFallbackBanner(reason: fallbackReason),
                ...plans.map(
                  (plan) => Padding(
                    padding: const EdgeInsets.only(bottom: 20),
                    child: PlanCard(
                      plan: plan,
                      onPrimaryAction: () => plan.isFree
                          ? _showApprovalDialog(context, plan)
                          : _showPixCheckout(context, ref, plan),
                      onCopyPixKey: plan.effectivePixKey != null
                          ? () => _copyToClipboard(
                                context,
                                plan.effectivePixKey!,
                                'Chave Pix copiada para a área de transferência.',
                              )
                          : null,
                    ),
                  ),
                ),
                const _PixChargeHistorySection(),
                const _PixSupportCard(),
              ],
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, stackTrace) => _ErrorView(
          message: 'Não foi possível carregar os planos agora.',
          onRetry: () => ref.invalidate(plansProvider),
        ),
      ),
    );
  }
}

Future<void> _copyToClipboard(
  BuildContext context,
  String value,
  String successMessage,
) async {
  await Clipboard.setData(ClipboardData(text: value));
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(content: Text(successMessage)),
  );
}

Future<void> _showPixCheckout(
  BuildContext context,
  WidgetRef ref,
  Plan plan,
) async {
  final checkoutRepository = ref.read(pixCheckoutRepositoryProvider);
  final chargeRepository = ref.read(pixChargeRepositoryProvider);

  await showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (sheetContext) {
      return _PixCheckoutSheet(
        plan: plan,
        createCharge: () =>
            checkoutRepository.createCharge(planId: plan.id),
        watchCharge: (initial) =>
            chargeRepository.watchCharge(initial.id, initial: initial),
      );
    },
  );
}

void _showApprovalDialog(BuildContext context, Plan plan) {
  showDialog<void>(
    context: context,
    builder: (dialogContext) {
      return AlertDialog(
        title: const Text('Solicitar aprovação'),
        content: Text(
          'Confirme o envio da solicitação de aprovação para o plano "${plan.name}". '
          'O super admin será notificado e o status aparecerá como pendente até a decisão.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () {
              Navigator.of(dialogContext).pop();
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Solicitação enviada para aprovação.'),
                ),
              );
            },
            child: const Text('Confirmar solicitação'),
          ),
        ],
      );
    },
  );
}

class _PixCheckoutSheet extends ConsumerStatefulWidget {
  const _PixCheckoutSheet({
    required this.plan,
    required this.createCharge,
    this.watchCharge,
  });

  final Plan plan;
  final Future<PixCharge> Function() createCharge;
  final Stream<PixCharge> Function(PixCharge charge)? watchCharge;

  @override
  ConsumerState<_PixCheckoutSheet> createState() => _PixCheckoutSheetState();
}

class _PixCheckoutSheetState extends ConsumerState<_PixCheckoutSheet> {
  late Future<PixCharge> _chargeFuture;

  @override
  void initState() {
    super.initState();
    _chargeFuture = widget.createCharge();
  }

  void _retry() {
    setState(() {
      _chargeFuture = widget.createCharge();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<PixCharge>(
      future: _chargeFuture,
      builder: (context, snapshot) {
        final bottomPadding = MediaQuery.of(context).viewInsets.bottom;

        if (snapshot.connectionState != ConnectionState.done) {
          return Padding(
            padding: EdgeInsets.fromLTRB(24, 24, 24, bottomPadding + 24),
            child: const _PixCheckoutLoading(),
          );
        }

        if (snapshot.hasError) {
          final message = snapshot.error is PixCheckoutException
              ? (snapshot.error as PixCheckoutException).message
              : 'Não foi possível gerar a cobrança Pix.';
          final fallback = widget.plan.pix != null
              ? widget.plan.createFallbackCharge()
              : null;

          if (fallback != null) {
            return Padding(
              padding: EdgeInsets.fromLTRB(24, 24, 24, bottomPadding + 24),
              child: _PixChargeContent(
                plan: widget.plan,
                charge: fallback,
                isFallback: true,
                errorMessage: message,
                onRetry: _retry,
              ),
            );
          }

          return Padding(
            padding: EdgeInsets.fromLTRB(24, 24, 24, bottomPadding + 24),
            child: _PixChargeError(
              message: message,
              onRetry: _retry,
            ),
          );
        }

        final charge = snapshot.requireData;
        final watchCharge = widget.watchCharge;
        if (watchCharge != null) {
          return StreamBuilder<PixCharge>(
            stream: watchCharge(charge),
            initialData: charge,
            builder: (context, chargeSnapshot) {
              final currentCharge = chargeSnapshot.data ?? charge;
              final error = chargeSnapshot.error;

              return Padding(
                padding:
                    EdgeInsets.fromLTRB(24, 24, 24, bottomPadding + 24),
                child: _PixChargeContent(
                  plan: widget.plan,
                  charge: currentCharge,
                  isFallback: false,
                  errorMessage: error is PixCheckoutException
                      ? error.message
                      : error?.toString(),
                  onRetry: currentCharge.isFinal ? null : _retry,
                ),
              );
            },
          );
        }

        return Padding(
          padding: EdgeInsets.fromLTRB(24, 24, 24, bottomPadding + 24),
          child: _PixChargeContent(
            plan: widget.plan,
            charge: charge,
            isFallback: false,
            onRetry: charge.isFinal ? null : _retry,
          ),
        );
      },
    );
  }
}

class _PixCheckoutLoading extends StatelessWidget {
  const _PixCheckoutLoading();

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        const SizedBox(height: 24),
        const CircularProgressIndicator(),
        const SizedBox(height: 16),
        Text(
          'Gerando cobrança Pix... ',
          style: Theme.of(context).textTheme.bodyMedium,
        ),
        const SizedBox(height: 24),
      ],
    );
  }
}

class _PixChargeContent extends StatelessWidget {
  const _PixChargeContent({
    required this.plan,
    required this.charge,
    required this.isFallback,
    this.errorMessage,
    this.onRetry,
  });

  final Plan plan;
  final PixCharge charge;
  final bool isFallback;
  final String? errorMessage;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final dateFormat = DateFormat('dd/MM/yyyy HH:mm', 'pt_BR');
    final pixKey = charge.pixKey ?? plan.effectivePixKey;

    return SingleChildScrollView(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Cobrança Pix',
            style: theme.textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Escaneie o QR Code ou copie o código copia e cola para concluir o pagamento do ${plan.name}.',
            style: theme.textTheme.bodyMedium,
          ),
          if (isFallback || errorMessage != null) ...[
            const SizedBox(height: 16),
            _PixCheckoutNotice(
              message: errorMessage ??
                  'Estamos exibindo os dados locais do plano. Gere novamente quando estiver conectado ao backend.',
              isWarning: true,
              onRetry: onRetry,
            ),
          ],
          if (!isFallback && charge.isPaid) ...[
            const SizedBox(height: 16),
            _PixCheckoutNotice(
              message:
                  'Pagamento confirmado! Liberaremos o plano e enviaremos o recibo por e-mail em instantes.',
            ),
          ],
          if (!isFallback && charge.isExpired) ...[
            const SizedBox(height: 16),
            _PixCheckoutNotice(
              message:
                  'Esta cobrança expirou. Gere uma nova para continuar o checkout.',
              isWarning: true,
              onRetry: onRetry,
            ),
          ],
          const SizedBox(height: 24),
          Center(
            child: DecoratedBox(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 18,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: _PixQrDisplay(charge: charge),
              ),
            ),
          ),
          const SizedBox(height: 24),
          Text(
            'Código copia e cola',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 8),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: theme.colorScheme.surfaceVariant,
              borderRadius: BorderRadius.circular(12),
            ),
            child: SelectableText(
              charge.copyAndPasteCode,
              style: theme.textTheme.bodySmall?.copyWith(
                fontFamily: 'monospace',
                height: 1.4,
              ),
            ),
          ),
          const SizedBox(height: 16),
          PrimaryButton(
            label: 'Copiar código copia e cola',
            icon: Icons.copy_outlined,
            onPressed: () async {
              await Clipboard.setData(
                ClipboardData(text: charge.copyAndPasteCode),
              );
              messenger.showSnackBar(
                const SnackBar(content: Text('Código copia e cola copiado.')),
              );
            },
          ),
          const SizedBox(height: 12),
          if (pixKey != null)
            OutlinedButton.icon(
              onPressed: () async {
                await Clipboard.setData(ClipboardData(text: pixKey));
                messenger.showSnackBar(
                  const SnackBar(content: Text('Chave Pix copiada.')),
                );
              },
              icon: const Icon(Icons.key_outlined),
              label: const Text('Copiar chave Pix'),
            ),
          if (charge.txid != null) ...[
            const SizedBox(height: 12),
            Text(
              'Txid: ${charge.txid}',
              style: theme.textTheme.bodySmall,
            ),
          ],
          const SizedBox(height: 12),
          Text(
            'Status: ${_statusLabel(charge.status)}',
            style: theme.textTheme.bodySmall,
          ),
          Text(
            'Válido até ${dateFormat.format(charge.expiresAt)}.',
            style: theme.textTheme.bodySmall,
          ),
          if (onRetry != null) ...[
            const SizedBox(height: 20),
            TextButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: const Text('Gerar novamente'),
            ),
          ],
        ],
      ),
    );
  }

  String _statusLabel(PixChargeStatus status) {
    return switch (status) {
      PixChargeStatus.paid => 'Pago',
      PixChargeStatus.expired => 'Expirado',
      PixChargeStatus.pending => 'Pendente',
    };
  }
}

class _PixQrDisplay extends StatelessWidget {
  const _PixQrDisplay({required this.charge});

  final PixCharge charge;

  @override
  Widget build(BuildContext context) {
    final base64 = charge.qrCodeBase64;
    if (base64 != null && base64.isNotEmpty) {
      final sanitized = base64.contains(',') ? base64.split(',').last : base64;
      final bytes = base64Decode(sanitized);
      return ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: Image.memory(
          bytes,
          width: 220,
          height: 220,
          fit: BoxFit.cover,
        ),
      );
    }

    final qrUrl = charge.qrCodeUrl;
    if (qrUrl != null && qrUrl.isNotEmpty) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: Image.network(
          qrUrl,
          width: 220,
          height: 220,
          fit: BoxFit.cover,
          errorBuilder: (_, __, ___) => _buildFallbackQr(),
        ),
      );
    }

    return _buildFallbackQr();
  }

  Widget _buildFallbackQr() {
    return QrImageView(
      data: charge.copyAndPasteCode,
      backgroundColor: Colors.white,
      size: 220,
    );
  }
}

class _PixCheckoutNotice extends StatelessWidget {
  const _PixCheckoutNotice({
    required this.message,
    this.isWarning = false,
    this.onRetry,
  });

  final String message;
  final bool isWarning;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final background = isWarning
        ? colorScheme.tertiaryContainer
        : colorScheme.surfaceVariant;
    final foreground = isWarning
        ? colorScheme.onTertiaryContainer
        : colorScheme.onSurfaceVariant;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            isWarning ? Icons.wifi_off_outlined : Icons.info_outline,
            color: foreground,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  message,
                  style: theme.textTheme.bodySmall?.copyWith(color: foreground),
                ),
                if (onRetry != null) ...[
                  const SizedBox(height: 8),
                  GestureDetector(
                    onTap: onRetry,
                    child: Text(
                      'Tentar novamente',
                      style: theme.textTheme.labelMedium?.copyWith(
                        color: foreground,
                        fontWeight: FontWeight.w600,
                        decoration: TextDecoration.underline,
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _PixChargeError extends StatelessWidget {
  const _PixChargeError({
    required this.message,
    this.onRetry,
  });

  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Não foi possível gerar a cobrança',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 12),
        Text(
          message,
          style: theme.textTheme.bodyMedium,
        ),
        const SizedBox(height: 20),
        if (onRetry != null)
          FilledButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: const Text('Tentar novamente'),
          ),
      ],
    );
  }
}

class _PixChargeHistorySection extends ConsumerWidget {
  const _PixChargeHistorySection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final historyAsync = ref.watch(pixChargeHistoryProvider);
    return historyAsync.when(
      data: (charges) {
        if (charges.isEmpty) {
          return const SizedBox.shrink();
        }

        final theme = Theme.of(context);
        final dateFormat = DateFormat('dd/MM HH:mm', 'pt_BR');
        final currencyFormatter = NumberFormat.simpleCurrency(locale: 'pt_BR');

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 8),
            Text(
              'Histórico recente de Pix',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 12),
            ...charges.take(5).map(
              (charge) => _PixChargeHistoryTile(
                charge: charge,
                currencyFormatter: currencyFormatter,
                dateFormat: dateFormat,
              ),
            ),
            if (charges.length > 5)
              Padding(
                padding: const EdgeInsets.only(top: 4, bottom: 12),
                child: Text(
                  'Exibindo 5 de ${charges.length} cobranças recentes.',
                  style: theme.textTheme.bodySmall,
                ),
              )
            else
              const SizedBox(height: 12),
          ],
        );
      },
      loading: () => const SizedBox.shrink(),
      error: (error, _) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: _PixCheckoutNotice(
          message:
              'Não foi possível carregar o histórico local de cobranças Pix.',
          isWarning: true,
        ),
      ),
    );
  }
}

class _PixChargeHistoryTile extends StatelessWidget {
  const _PixChargeHistoryTile({
    required this.charge,
    required this.currencyFormatter,
    required this.dateFormat,
  });

  final PixCharge charge;
  final NumberFormat currencyFormatter;
  final DateFormat dateFormat;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final messenger = ScaffoldMessenger.of(context);

    final (Color background, Color foreground, IconData icon) = switch (charge.status) {
      PixChargeStatus.paid => (
          scheme.primaryContainer,
          scheme.onPrimaryContainer,
          Icons.verified_outlined,
        ),
      PixChargeStatus.expired => (
          scheme.errorContainer,
          scheme.onErrorContainer,
          Icons.timer_off_outlined,
        ),
      PixChargeStatus.pending => (
          scheme.surfaceVariant,
          scheme.onSurfaceVariant,
          Icons.hourglass_bottom,
        ),
    };

    final statusLabel = switch (charge.status) {
      PixChargeStatus.paid => 'Pagamento confirmado',
      PixChargeStatus.expired => 'Cobrança expirada',
      PixChargeStatus.pending => 'Cobrança pendente',
    };

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(icon, color: foreground),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      statusLabel,
                      style: theme.textTheme.titleSmall?.copyWith(
                        color: foreground,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Plano: ${charge.planId}',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: foreground,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Atualizado em ${dateFormat.format(charge.updatedAt)}',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: foreground,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Expira em ${dateFormat.format(charge.expiresAt)}',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: foreground,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Text(
                currencyFormatter.format(charge.amount),
                style: theme.textTheme.titleSmall?.copyWith(
                  color: foreground,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Align(
            alignment: Alignment.centerLeft,
            child: TextButton.icon(
              style: TextButton.styleFrom(foregroundColor: foreground),
              onPressed: () async {
                await Clipboard.setData(
                  ClipboardData(text: charge.copyAndPasteCode),
                );
                messenger.showSnackBar(
                  const SnackBar(
                    content: Text('Código copia e cola copiado.'),
                  ),
                );
              },
              icon: const Icon(Icons.copy_outlined),
              label: const Text('Copiar código'),
            ),
          ),
        ],
      ),
    );
  }
}

class _PlanFallbackBanner extends StatelessWidget {
  const _PlanFallbackBanner({this.reason});

  final String? reason;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final background = scheme.surfaceVariant;
    final foreground = scheme.onSurfaceVariant;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            Icons.wifi_off_rounded,
            color: foreground,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Mostrando planos offline',
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: foreground,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  reason?.isNotEmpty == true
                      ? 'Não foi possível atualizar os planos agora. Motivo: $reason. '
                          'Os dados abaixo vêm do cache mais recente.'
                      : 'Não foi possível atualizar os planos agora. Os dados abaixo vêm do cache mais recente.',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: foreground.withOpacity(0.9),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyPlansView extends StatelessWidget {
  const _EmptyPlansView({this.wasFallback = false});

  final bool wasFallback;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final outline = theme.colorScheme.outline;

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.layers_outlined,
              size: 48,
              color: outline,
            ),
            const SizedBox(height: 16),
            Text(
              'Nenhum plano disponível no momento.',
              style: theme.textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            Text(
              wasFallback
                  ? 'Não encontramos planos nem mesmo no cache. Verifique sua conexão e tente novamente.'
                  : 'Volte mais tarde ou entre em contato com o suporte para conferir o status das assinaturas.',
              style: theme.textTheme.bodyMedium,
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  const _ErrorView({
    required this.message,
    required this.onRetry,
  });

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.wifi_off_outlined,
              size: 48,
              color: Theme.of(context).colorScheme.error,
            ),
            const SizedBox(height: 16),
            Text(
              message,
              style: Theme.of(context).textTheme.titleMedium,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 20),
            FilledButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: const Text('Tentar novamente'),
            ),
          ],
        ),
      ),
    );
  }
}

class _PixSupportCard extends StatelessWidget {
  const _PixSupportCard();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      margin: const EdgeInsets.only(top: 8, bottom: 32),
      color: colorScheme.secondaryContainer,
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Precisa de ajuda com o Pix?',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
                color: colorScheme.onSecondaryContainer,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Fale com o suporte para revisar aprovações de Planos Grátis para Alunos, gerar nova cobrança ou confirmar pagamentos atrasados.',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: colorScheme.onSecondaryContainer,
              ),
            ),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              style: OutlinedButton.styleFrom(
                foregroundColor: colorScheme.onSecondaryContainer,
                side: BorderSide(color: colorScheme.onSecondaryContainer.withOpacity(0.4)),
              ),
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Abrindo chat de suporte Pix...'),
                  ),
                );
              },
              icon: const Icon(Icons.support_agent_outlined),
              label: const Text('Contactar suporte'),
            ),
          ],
        ),
      ),
    );
  }
}
