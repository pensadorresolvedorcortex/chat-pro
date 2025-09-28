import 'package:equatable/equatable.dart';

import 'pix_charge.dart';

enum PlanType { paid, freeStudent }

enum PlanApprovalStatus { approved, pending, rejected }

class Plan extends Equatable {
  const Plan({
    required this.id,
    required this.name,
    required this.description,
    required this.type,
    required this.periodicity,
    required this.price,
    required this.currency,
    required this.benefits,
    required this.approvalStatus,
    this.pixKey,
    this.pix,
    this.approvedBy,
    this.approvedAt,
    this.updatedAt,
    this.lastRequestedAt,
    this.isFeatured = false,
  });

  factory Plan.fromJson(Map<String, dynamic> json) {
    final id = _readString(json, const ['id', 'planoId', 'uuid']);
    final name = _readString(json, const ['nome', 'name', 'titulo']);
    if (id == null || name == null) {
      throw FormatException('Plano inválido: id ou nome ausente.');
    }

    final rawDescription = _readString(
          json,
          const ['descricao', 'description', 'resumo'],
        ) ??
        '';

    final pixData = _readMap(json, const ['pix', 'pixCheckout']);
    final pix = pixData != null ? PixCheckout.fromJson(pixData) : null;

    final pixKeyFromRoot = _readString(
      json,
      const ['chavePix', 'pixKey'],
    );
    final pixKeyFromPix =
        pixData != null ? _readString(pixData, const ['chave', 'pixKey']) : null;

    return Plan(
      id: id,
      name: name,
      description: rawDescription.trim(),
      type: _mapType(_readString(json, const ['tipo', 'type'])),
      periodicity:
          (_readString(json, const ['periodicidade', 'intervalo']) ?? 'mensal')
              .toLowerCase(),
      price: _readPrice(json),
      currency: _readString(json, const ['moeda', 'currency']) ?? 'BRL',
      benefits: _readStringList(json, const ['beneficios', 'benefits']),
      approvalStatus:
          _mapApprovalStatus(_readString(json, const ['statusAprovacao', 'approvalStatus'])),
      pixKey: pixKeyFromRoot ?? pixKeyFromPix,
      pix: pix,
      approvedBy: _readString(json, const ['aprovadoPor', 'approvedBy']),
      approvedAt: _parseDate(
        _readString(json, const ['aprovadoEm', 'approvedAt']),
      ),
      updatedAt: _parseDate(
        _readString(json, const ['atualizadoEm', 'updatedAt']),
      ),
      lastRequestedAt: _parseDate(
        _readString(json, const ['ultimaSolicitacao', 'lastRequestedAt']),
      ),
      isFeatured: json['destaque'] == true || json['featured'] == true,
    );
  }

  final String id;
  final String name;
  final String description;
  final PlanType type;
  final String periodicity;
  final double price;
  final String currency;
  final List<String> benefits;
  final PlanApprovalStatus approvalStatus;
  final String? pixKey;
  final PixCheckout? pix;
  final String? approvedBy;
  final DateTime? approvedAt;
  final DateTime? updatedAt;
  final DateTime? lastRequestedAt;
  final bool isFeatured;

  bool get isFree => price == 0;

  bool get requiresApproval => type == PlanType.freeStudent;

  String? get effectivePixKey => pix?.pixKey ?? pixKey;

  String get typeLabel => switch (type) {
        PlanType.paid => 'Plano pago',
        PlanType.freeStudent => 'Plano Grátis para Alunos',
      };

  String get approvalStatusLabel => switch (approvalStatus) {
        PlanApprovalStatus.approved => 'Aprovado',
        PlanApprovalStatus.pending => 'Pendente',
        PlanApprovalStatus.rejected => 'Rejeitado',
      };

  String get periodicityLabel => switch (periodicity) {
        'mensal' => 'mês',
        'anual' => 'ano',
        'trimestral' => 'trimestre',
        'semestral' => 'semestre',
        _ => periodicity,
      };

  PixCharge toOfflinePixCharge() {
    final pix = this.pix;
    if (pix == null) {
      throw StateError(
        'Plano $id não possui dados Pix locais para fallback.',
      );
    }

    final now = DateTime.now();
    return PixCharge(
      id: 'offline-$id',
      planId: id,
      amount: pix.amount ?? price,
      currency: pix.currency ?? currency,
      status: PixChargeStatus.pending,
      copyAndPasteCode: pix.copyAndPasteCode,
      qrCodeUrl: null,
      qrCodeBase64: null,
      pixKey: pix.pixKey ?? pixKey,
      txid: null,
      expiresAt: pix.expiresAt ?? now.add(const Duration(minutes: 30)),
      createdAt: now,
      updatedAt: now,
    );
  }

  static PlanType _mapType(String? raw) {
    return switch (raw) {
      'pago' => PlanType.paid,
      'paid' => PlanType.paid,
      'gratis_aluno' => PlanType.freeStudent,
      'free_student' => PlanType.freeStudent,
      'free' => PlanType.freeStudent,
      _ => PlanType.paid,
    };
  }

  static PlanApprovalStatus _mapApprovalStatus(String? raw) {
    return switch (raw) {
      'aprovado' => PlanApprovalStatus.approved,
      'approved' => PlanApprovalStatus.approved,
      'reprovado' => PlanApprovalStatus.rejected,
      'rejected' => PlanApprovalStatus.rejected,
      _ => PlanApprovalStatus.pending,
    };
  }

  @override
  List<Object?> get props => [
        id,
        name,
        description,
        type,
        periodicity,
        price,
        currency,
        benefits,
        approvalStatus,
        pixKey,
        pix,
        approvedBy,
        approvedAt,
        updatedAt,
        lastRequestedAt,
        isFeatured,
      ];
}

class PixCheckout extends Equatable {
  const PixCheckout({
    required this.pixKey,
    required this.pixKeyType,
    required this.copyAndPasteCode,
    this.provider,
    this.expiresAt,
    this.amount,
    this.currency,
  });

  factory PixCheckout.fromJson(Map<String, dynamic> json) {
    return PixCheckout(
      pixKey: json['chave'] as String,
      pixKeyType: (json['tipoChave'] as String?) ?? 'aleatoria',
      copyAndPasteCode: json['codigoCopiaCola'] as String,
      provider: json['provedor'] as String?,
      expiresAt: _parseDate(json['expiraEm'] as String?),
      amount: (json['valor'] as num?)?.toDouble(),
      currency: json['moeda'] as String?,
    );
  }

  final String pixKey;
  final String pixKeyType;
  final String copyAndPasteCode;
  final String? provider;
  final DateTime? expiresAt;
  final double? amount;
  final String? currency;

  @override
  List<Object?> get props => [
        pixKey,
        pixKeyType,
        copyAndPasteCode,
        provider,
        expiresAt,
        amount,
        currency,
      ];
}

List<String> _readStringList(
  Map<String, dynamic> json,
  List<String> keys,
) {
  for (final key in keys) {
    final value = json[key];
    if (value is List) {
      return value.whereType<String>().toList();
    }
  }
  return const [];
}

String? _readString(Map<String, dynamic> json, List<String> keys) {
  for (final key in keys) {
    final value = json[key];
    if (value == null) {
      continue;
    }

    if (value is String) {
      if (value.isNotEmpty) {
        return value;
      }
    } else {
      return value.toString();
    }
  }
  return null;
}

double _readPrice(Map<String, dynamic> json) {
  final priceString = _readString(json, const ['preco', 'valor', 'price']);
  if (priceString != null) {
    final parsed = double.tryParse(priceString.replaceAll(',', '.'));
    if (parsed != null) {
      return parsed;
    }
  }

  final cents = json['precoCentavos'] ?? json['valorCentavos'];
  if (cents is num) {
    return cents.toDouble() / 100;
  }

  final priceNum = json['preco'] ?? json['valor'];
  if (priceNum is num) {
    return priceNum.toDouble();
  }

  return 0;
}

Map<String, dynamic>? _readMap(
  Map<String, dynamic> json,
  List<String> keys,
) {
  for (final key in keys) {
    final value = json[key];
    if (value is Map<String, dynamic>) {
      return value;
    }
  }
  return null;
}

DateTime? _parseDate(String? raw) {
  if (raw == null || raw.isEmpty) {
    return null;
  }
  return DateTime.tryParse(raw)?.toLocal();
}
