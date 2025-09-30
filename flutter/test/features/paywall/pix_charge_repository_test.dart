import 'package:academia_da_comunicacao/features/paywall/domain/pix_charge.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('PixCharge', () {
    test('serializes to and from json map', () {
      final now = DateTime.parse('2024-06-30T12:00:00Z');
      final charge = PixCharge(
        id: 'charge-123',
        planId: 'plano-mensal-plus',
        amount: 64.9,
        currency: 'BRL',
        status: PixChargeStatus.pending,
        copyAndPasteCode: '00020126360014BR.GOV.BCB.PIX0114pix@academia.com0208Academia52040000530398654046.905802BR5914Academia Demo6009Sao Paulo62070503***6304B14E',
        qrCodeUrl: 'https://cdn.academia/qrcode.png',
        qrCodeBase64: 'iVBORw0KGgoAAAANSUhEUgAA',
        pixKey: 'pix@academia.com',
        txid: 'TX1234567',
        expiresAt: now.add(const Duration(minutes: 30)),
        createdAt: now,
        updatedAt: now,
      );

      final json = charge.toJson();
      final parsed = PixCharge.fromJson(json);

      expect(parsed, equals(charge));
      expect(parsed.isFinal, isFalse);
      expect(parsed.copyAndPasteCode.contains('000201'), isTrue);
    });

    test('maps assorted status strings', () {
      final payload = {
        'id': 'charge-789',
        'planoId': 'plano-pro-anual',
        'amount': 529.0,
        'currency': 'BRL',
        'status': 'confirmado',
        'codigoCopiaCola': 'ABC123',
        'expiraEm': '2024-07-01T12:00:00Z',
        'createdAt': '2024-07-01T10:00:00Z',
        'updatedAt': '2024-07-01T10:30:00Z',
      };

      final parsed = PixCharge.fromJson(payload);
      expect(parsed.status, PixChargeStatus.paid);
      expect(parsed.isFinal, isTrue);
    });
  });
}
