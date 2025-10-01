enum PixChargeStatus {
  pending,
  paid,
  expired,
  failed,
  unknown,
}

PixChargeStatus _statusFromString(String? value) {
  if (value == null) {
    return PixChargeStatus.unknown;
  }
  switch (value.toLowerCase()) {
    case 'pendente':
    case 'aguardando':
    case 'em_aberto':
    case 'pending':
      return PixChargeStatus.pending;
    case 'confirmado':
    case 'pago':
    case 'paid':
      return PixChargeStatus.paid;
    case 'expirado':
    case 'expirou':
    case 'expired':
      return PixChargeStatus.expired;
    case 'falhou':
    case 'cancelado':
    case 'failed':
      return PixChargeStatus.failed;
    default:
      return PixChargeStatus.unknown;
  }
}

String _statusToString(PixChargeStatus status) {
  switch (status) {
    case PixChargeStatus.pending:
      return 'pendente';
    case PixChargeStatus.paid:
      return 'confirmado';
    case PixChargeStatus.expired:
      return 'expirado';
    case PixChargeStatus.failed:
      return 'falhou';
    case PixChargeStatus.unknown:
      return 'desconhecido';
  }
}

class PixCharge {
  const PixCharge({
    required this.id,
    required this.planId,
    required this.amount,
    required this.currency,
    required this.status,
    required this.copyAndPasteCode,
    this.qrCodeUrl,
    this.qrCodeBase64,
    this.pixKey,
    this.txid,
    this.expiresAt,
    required this.createdAt,
    required this.updatedAt,
  });

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
  final DateTime? expiresAt;
  final DateTime createdAt;
  final DateTime updatedAt;

  bool get isFinal =>
      status == PixChargeStatus.paid ||
      status == PixChargeStatus.expired ||
      status == PixChargeStatus.failed;

  PixCharge copyWith({
    PixChargeStatus? status,
    String? copyAndPasteCode,
    String? qrCodeUrl,
    String? qrCodeBase64,
    String? pixKey,
    String? txid,
    DateTime? expiresAt,
    DateTime? updatedAt,
  }) {
    return PixCharge(
      id: id,
      planId: planId,
      amount: amount,
      currency: currency,
      status: status ?? this.status,
      copyAndPasteCode: copyAndPasteCode ?? this.copyAndPasteCode,
      qrCodeUrl: qrCodeUrl ?? this.qrCodeUrl,
      qrCodeBase64: qrCodeBase64 ?? this.qrCodeBase64,
      pixKey: pixKey ?? this.pixKey,
      txid: txid ?? this.txid,
      expiresAt: expiresAt ?? this.expiresAt,
      createdAt: createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'planoId': planId,
      'amount': amount,
      'currency': currency,
      'status': _statusToString(status),
      'codigoCopiaCola': copyAndPasteCode,
      if (qrCodeUrl != null) 'qrCodeUrl': qrCodeUrl,
      if (qrCodeBase64 != null) 'qrCodeBase64': qrCodeBase64,
      if (pixKey != null) 'chavePix': pixKey,
      if (txid != null) 'txid': txid,
      if (expiresAt != null) 'expiraEm': expiresAt!.toIso8601String(),
      'createdAt': createdAt.toIso8601String(),
      'updatedAt': updatedAt.toIso8601String(),
    };
  }

  factory PixCharge.fromJson(Map<String, dynamic> json) {
    DateTime? parseDate(dynamic value) {
      if (value == null) {
        return null;
      }
      if (value is DateTime) {
        return value;
      }
      if (value is String && value.isNotEmpty) {
        return DateTime.parse(value);
      }
      return null;
    }

    final status = _statusFromString(json['status'] as String?);
    return PixCharge(
      id: json['id'] as String,
      planId: json['planoId'] as String,
      amount: (json['amount'] as num).toDouble(),
      currency: json['currency'] as String,
      status: status,
      copyAndPasteCode: json['codigoCopiaCola'] as String? ?? '',
      qrCodeUrl: json['qrCodeUrl'] as String?,
      qrCodeBase64: json['qrCodeBase64'] as String?,
      pixKey: json['chavePix'] as String?,
      txid: json['txid'] as String?,
      expiresAt: parseDate(json['expiraEm']),
      createdAt: parseDate(json['createdAt']) ?? DateTime.now(),
      updatedAt: parseDate(json['updatedAt']) ?? DateTime.now(),
    );
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    if (other.runtimeType != runtimeType) return false;
    return other is PixCharge &&
        other.id == id &&
        other.planId == planId &&
        other.amount == amount &&
        other.currency == currency &&
        other.status == status &&
        other.copyAndPasteCode == copyAndPasteCode &&
        other.qrCodeUrl == qrCodeUrl &&
        other.qrCodeBase64 == qrCodeBase64 &&
        other.pixKey == pixKey &&
        other.txid == txid &&
        other.expiresAt == expiresAt &&
        other.createdAt == createdAt &&
        other.updatedAt == updatedAt;
  }

  @override
  int get hashCode => Object.hash(
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
      );

  @override
  String toString() {
    return 'PixCharge(id: $id, planId: $planId, status: $status, amount: $amount)';
  }
}
