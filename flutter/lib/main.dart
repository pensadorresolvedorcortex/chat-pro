import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app.dart';
import 'core/theme/app_fonts.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AppFonts.ensureLoaded();
  runApp(const ProviderScope(child: AcademiaApp()));
}
