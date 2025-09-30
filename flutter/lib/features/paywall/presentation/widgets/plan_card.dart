import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../shared/widgets/primary_button.dart';
import '../../domain/plan.dart';

class PlanCard extends StatelessWidget {
  const PlanCard({
    required this.plan,
    required this.onPrimaryAction,
    this.onCopyPixKey,
    super.key,
  });

  static final NumberFormat _currencyFormatter =
      NumberFormat.simpleCurrency(locale: 'pt_BR');
  static final DateFormat _dateFormatter =
      DateFormat('dd/MM/yyyy HH:mm', 'pt_BR');

  final Plan plan;
  final VoidCallback onPrimaryAction;
  final VoidCallback? onCopyPixKey;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        plan.name,
                        style: theme.textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      if (plan.description.isNotEmpty) ...[
                        const SizedBox(height: 6),
                        Text(
                          plan.description,
                          style: theme.textTheme.bodyMedium,
                        ),
                      ],
                    ],
                  ),
                ),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _StatusChip(plan: plan),
                    _TypeChip(plan: plan),
                    if (plan.isFeatured)
                      Chip(
                        label: const Text('Recomendado'),
                        backgroundColor: colorScheme.secondaryContainer,
                        labelStyle: theme.textTheme.labelMedium?.copyWith(
                          color: colorScheme.onSecondaryContainer,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 20),
            Text(
              plan.isFree
                  ? 'Gratuito'
                  : '${_currencyFormatter.format(plan.price)} por ${plan.periodicityLabel}',
              style: theme.textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.w700,
                color: colorScheme.primary,
              ),
            ),
            const SizedBox(height: 16),
            ...plan.benefits.map(
              (benefit) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(
                      Icons.check_circle_outline,
                      size: 20,
                      color: colorScheme.primary,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        benefit,
                        style: theme.textTheme.bodyMedium,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            if (plan.effectivePixKey != null) ...[
              const SizedBox(height: 20),
              Text(
                'Pagamento Pix',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              _PixKeyTile(
                plan: plan,
                onCopyPixKey: onCopyPixKey,
              ),
            ],
            if (plan.requiresApproval) ...[
              const SizedBox(height: 20),
              Text(
                'Este plano precisa de aprovação do super admin antes da liberação para alunos.',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              if (plan.lastRequestedAt != null) ...[
                const SizedBox(height: 6),
                Text(
                  'Última solicitação: ${_dateFormatter.format(plan.lastRequestedAt!)}',
                  style: theme.textTheme.bodySmall,
                ),
              ],
            ],
            const SizedBox(height: 24),
            PrimaryButton(
              label: plan.isFree ? 'Solicitar aprovação' : 'Gerar cobrança Pix',
              icon: plan.isFree ? Icons.verified_user_outlined : Icons.qr_code_2,
              onPressed: onPrimaryAction,
            ),
          ],
        ),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.plan});

  final Plan plan;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final (background, foreground) = switch (plan.approvalStatus) {
      PlanApprovalStatus.approved => (scheme.primaryContainer, scheme.onPrimaryContainer),
      PlanApprovalStatus.pending => (scheme.tertiaryContainer, scheme.onTertiaryContainer),
      PlanApprovalStatus.rejected => (scheme.errorContainer, scheme.onErrorContainer),
    };

    return Chip(
      label: Text(plan.approvalStatusLabel),
      backgroundColor: background,
      labelStyle: theme.textTheme.labelMedium?.copyWith(
        color: foreground,
        fontWeight: FontWeight.w600,
      ),
    );
  }
}

class _TypeChip extends StatelessWidget {
  const _TypeChip({required this.plan});

  final Plan plan;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    return Chip(
      label: Text(plan.typeLabel),
      backgroundColor: scheme.surfaceVariant,
      labelStyle: theme.textTheme.labelMedium?.copyWith(
        color: scheme.onSurfaceVariant,
        fontWeight: FontWeight.w600,
      ),
    );
  }
}

class _PixKeyTile extends StatelessWidget {
  const _PixKeyTile({
    required this.plan,
    this.onCopyPixKey,
  });

  final Plan plan;
  final VoidCallback? onCopyPixKey;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final pixKey = plan.effectivePixKey;

    if (pixKey == null) {
      return const SizedBox.shrink();
    }

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceVariant.withOpacity(0.6),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            Icons.key_outlined,
            color: theme.colorScheme.primary,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  pixKey,
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  plan.pix?.provider != null
                      ? 'Provedor: ${plan.pix!.provider}'
                      : plan.pix != null
                          ? 'Chave ${plan.pix!.pixKeyType.toUpperCase()}'
                          : 'Chave Pix dedicada',
                  style: theme.textTheme.bodySmall,
                ),
                if (plan.pix?.expiresAt != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Expira em ${DateFormat('dd/MM/yyyy HH:mm', 'pt_BR').format(plan.pix!.expiresAt!)}',
                    style: theme.textTheme.bodySmall,
                  ),
                ],
              ],
            ),
          ),
          if (onCopyPixKey != null)
            IconButton(
              onPressed: onCopyPixKey,
              icon: const Icon(Icons.copy_outlined),
              tooltip: 'Copiar chave Pix',
            ),
        ],
      ),
    );
  }
}
