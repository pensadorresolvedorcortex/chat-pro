import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  static const _primaryColor = Color(0xFF6645f6);
  static const _secondaryColor = Color(0xFF1dd3c4);
  static const _tertiaryColor = Color(0xFFe5be49);
  static const _errorColor = Color(0xFFdf5354);
  static const _backgroundDark = Color(0xFF0c3c64);

  static ThemeData get light {
    final textTheme = GoogleFonts.interTextTheme();
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: _primaryColor,
        primary: _primaryColor,
        secondary: _secondaryColor,
        tertiary: _tertiaryColor,
        error: _errorColor,
      ),
      textTheme: textTheme,
      scaffoldBackgroundColor: Colors.grey.shade50,
      appBarTheme: AppBarTheme(
        backgroundColor: Colors.grey.shade50,
        foregroundColor: _backgroundDark,
        elevation: 0,
        titleTextStyle: textTheme.titleLarge?.copyWith(
          fontWeight: FontWeight.w600,
          color: _backgroundDark,
        ),
      ),
      inputDecorationTheme: const InputDecorationTheme(
        border: OutlineInputBorder(),
      ),
      chipTheme: ChipThemeData(
        shape: const StadiumBorder(),
        backgroundColor: _secondaryColor.withOpacity(0.1),
        selectedColor: _secondaryColor,
        labelStyle: textTheme.bodyMedium?.copyWith(
          color: _backgroundDark,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  static ThemeData get dark {
    final textTheme = GoogleFonts.interTextTheme(
      ThemeData(brightness: Brightness.dark).textTheme,
    );
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: ColorScheme.fromSeed(
        brightness: Brightness.dark,
        seedColor: _backgroundDark,
        primary: _secondaryColor,
        secondary: _primaryColor,
        tertiary: _tertiaryColor,
        error: _errorColor,
      ),
      scaffoldBackgroundColor: _backgroundDark,
      appBarTheme: AppBarTheme(
        backgroundColor: _backgroundDark,
        foregroundColor: Colors.white,
        elevation: 0,
        titleTextStyle: textTheme.titleLarge?.copyWith(
          fontWeight: FontWeight.w600,
          color: Colors.white,
        ),
      ),
      textTheme: textTheme,
      chipTheme: ChipThemeData(
        shape: const StadiumBorder(),
        backgroundColor: Colors.white.withOpacity(0.1),
        selectedColor: _secondaryColor,
        labelStyle: textTheme.bodyMedium?.copyWith(
          color: Colors.white,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
