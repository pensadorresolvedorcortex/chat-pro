import 'package:equatable/equatable.dart';

enum PixChargeStatus { pending, paid, expired }

class PixCharge extends Equatable {
  const PixCharge({
    required this.id,
    required this.planId,
    required this.amount,
    required this.currency,
    required this.status,
    required this.copyAndPasteCode,
    required this.expiresAt,
    required this.createdAt,
    required this.updatedAt,
    this.qrCodeUrl,
    this.qrCodeBase64,
    this.pixKey,
    this.txid,
  });

  factory PixCharge.fromJson(Map<String, dynamic> json) {
    final id = _readString(json, const ['id', 'cobrancaId', 'chargeId']);
    final planId =
        _readString(json, const ['planoId', 'planId', 'subscriptionPlanId']);
    final copyAndPasteCode = _readString(
      json,
      const [
        'codigoCopiaCola',
        'codigoCopiaECola',
        'copyAndPasteCode',
        'codigo',
      ],
    );

    if (id == null || planId == null || copyAndPasteCode == null) {
      throw FormatException('Cobrança Pix inválida: campos obrigatórios ausentes.');
    }

    final createdAt = _readDateTime(
      json,
      const ['createdAt', 'criadoEm'],
    ) ??
        DateTime.now();
    final updatedAt = _readDateTime(
      json,
      const ['updatedAt', 'atualizadoEm'],
    ) ??
        createdAt;

    return PixCharge(
      id: id,
      planId: planId,
      amount: _readAmount(json),
      currency: _readString(json, const ['moeda', 'currency']) ?? 'BRL',
      status: _mapStatus(_readString(json, const ['status', 'situacao'])),
      copyAndPasteCode: copyAndPasteCode,
      qrCodeUrl: _readString(json, const ['qrCodeUrl', 'qrCode']),
      qrCodeBase64: _readString(json, const ['qrCodeBase64', 'qr_code_base64']),
      pixKey: _readString(json, const ['chavePix', 'pixKey', 'chave']),
      txid: _readString(json, const ['txid', 'transactionId']),
      expiresAt: _readDateTime(
        json,
        const ['expiresAt', 'expiraEm', 'expirationDate'],
        requiredField: true,
      )!,
      createdAt: createdAt,
      updatedAt: updatedAt,
    );
  }

  final String id;
  final String planId;
  final double amount;
  final String currency;
  final PixChargeStatus status;
  final String copyAndPasteCode;
  final String? qrCodeUrl;
  final String? qrCodeBase64;
  final String? pixKey;
  final String? txid;
  final DateTime expiresAt;
  final DateTime createdAt;
  final DateTime updatedAt;

  PixCharge copyWith({
    String? id,
    String? planId,
    double? amount,
    String? currency,
    PixChargeStatus? status,
    String? copyAndPasteCode,
    String? qrCodeUrl,
    String? qrCodeBase64,
    String? pixKey,
    String? txid,
    DateTime? expiresAt,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return PixCharge(
      id: id ?? this.id,
      planId: planId ?? this.planId,
      amount: amount ?? this.amount,
      currency: currency ?? this.currency,
      status: status ?? this.status,
      copyAndPasteCode: copyAndPasteCode ?? this.copyAndPasteCode,
      qrCodeUrl: qrCodeUrl ?? this.qrCodeUrl,
      qrCodeBase64: qrCodeBase64 ?? this.qrCodeBase64,
      pixKey: pixKey ?? this.pixKey,
      txid: txid ?? this.txid,
      expiresAt: expiresAt ?? this.expiresAt,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  bool get isPaid => status == PixChargeStatus.paid;

  bool get isExpired => status == PixChargeStatus.expired;

  bool get isFinal => isPaid || isExpired;

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'planoId': planId,
      'amount': amount,
      'currency': currency,
      'status': _statusToString(status),
      'codigoCopiaCola': copyAndPasteCode,
      'qrCodeUrl': qrCodeUrl,
      'qrCodeBase64': qrCodeBase64,
      'chavePix': pixKey,
      'txid': txid,
      'expiraEm': expiresAt.toIso8601String(),
      'createdAt': createdAt.toIso8601String(),
      'updatedAt': updatedAt.toIso8601String(),
    };
  }

  @override
  List<Object?> get props => [
        id,
        planId,
        amount,
        currency,
        status,
        copyAndPasteCode,
        qrCodeUrl,
        qrCodeBase64,
        pixKey,
        txid,
        expiresAt,
        createdAt,
        updatedAt,
      ];
}

PixChargeStatus _mapStatus(String? raw) {
  switch (raw) {
    case 'pago':
    case 'paid':
    case 'confirmado':
    case 'concluido':
      return PixChargeStatus.paid;
    case 'expirado':
    case 'cancelado':
    case 'failed':
    case 'expired':
      return PixChargeStatus.expired;
    default:
      return PixChargeStatus.pending;
  }
}

PixChargeStatus pixChargeStatusFromString(String? raw) => _mapStatus(raw);

String pixChargeStatusToString(PixChargeStatus status) => _statusToString(status);

String _statusToString(PixChargeStatus status) {
  switch (status) {
    case PixChargeStatus.paid:
      return 'paid';
    case PixChargeStatus.expired:
      return 'expired';
    case PixChargeStatus.pending:
    default:
      return 'pending';
  }
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

double _readAmount(Map<String, dynamic> json) {
  final amountString = _readString(json, const ['valor', 'amount']);
  if (amountString != null) {
    final parsed = double.tryParse(amountString.replaceAll(',', '.'));
    if (parsed != null) {
      return parsed;
    }
  }

  final cents = json['valorCentavos'] ?? json['amountCents'];
  if (cents is num) {
    return cents.toDouble() / 100;
  }

  final amount = json['valor'] ?? json['amount'];
  if (amount is num) {
    return amount.toDouble();
  }

  throw FormatException('Cobrança Pix inválida: valor ausente.');
}

DateTime? _readDateTime(
  Map<String, dynamic> json,
  List<String> keys, {
  bool requiredField = false,
}) {
  for (final key in keys) {
    final value = json[key];
    if (value is String && value.isNotEmpty) {
      final parsed = DateTime.tryParse(value);
      if (parsed != null) {
        return parsed.toLocal();
      }
    }
  }

  if (requiredField) {
    throw FormatException('Cobrança Pix inválida: data obrigatória ausente.');
  }

  return null;
}
