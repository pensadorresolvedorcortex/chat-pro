import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';

import 'app.dart';
import 'features/paywall/data/pix_charge_repository.dart';
import 'features/paywall/data/pix_charge_store.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);

  await Hive.initFlutter();
  final pixChargeStore = await PixChargeStore.create();
  await pixChargeStore.purge(
    olderThan: DateTime.now().subtract(const Duration(days: 15)),
  );

  runApp(
    ProviderScope(
      overrides: [
        pixChargeStoreProvider.overrideWithValue(pixChargeStore),
      ],
      child: const AcademiaApp(),
    ),
  );
}
