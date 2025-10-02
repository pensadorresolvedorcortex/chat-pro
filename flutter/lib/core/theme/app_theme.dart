import 'package:flutter/material.dart';

class AppTheme {
  static const _primaryColor = Color(0xFF6645f6);
  static const _secondaryColor = Color(0xFF1dd3c4);
  static const _tertiaryColor = Color(0xFFe5be49);
  static const _errorColor = Color(0xFFdf5354);
  static const _backgroundDark = Color(0xFF0c3c64);
  static const _fontFamily = 'New Science';

  static ThemeData get light {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: _primaryColor,
      primary: _primaryColor,
      secondary: _secondaryColor,
      tertiary: _tertiaryColor,
      error: _errorColor,
    );
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      fontFamily: _fontFamily,
    );
    return base.copyWith(
      scaffoldBackgroundColor: Colors.grey.shade50,
      textTheme: base.textTheme.apply(
        bodyColor: _backgroundDark,
        displayColor: _backgroundDark,
      ),
      appBarTheme: base.appBarTheme.copyWith(
        backgroundColor: _primaryColor,
        foregroundColor: Colors.white,
        elevation: 0,
        titleTextStyle: base.textTheme.titleLarge?.copyWith(
          fontWeight: FontWeight.w700,
          color: Colors.white,
        ),
        iconTheme: const IconThemeData(color: Colors.white),
        centerTitle: false,
      ),
      inputDecorationTheme: const InputDecorationTheme(
        border: OutlineInputBorder(),
      ),
      chipTheme: base.chipTheme.copyWith(
        shape: const StadiumBorder(),
        backgroundColor: _secondaryColor.withOpacity(0.12),
        selectedColor: _secondaryColor,
        labelStyle: base.textTheme.bodyMedium?.copyWith(
          color: _backgroundDark,
          fontWeight: FontWeight.w600,
        ),
      ),
      drawerTheme: base.drawerTheme.copyWith(
        backgroundColor: Colors.white,
      ),
    );
  }

  static ThemeData get dark {
    final colorScheme = ColorScheme.fromSeed(
      brightness: Brightness.dark,
      seedColor: _backgroundDark,
      primary: _secondaryColor,
      secondary: _primaryColor,
      tertiary: _tertiaryColor,
      error: _errorColor,
    );
    final base = ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: colorScheme,
      fontFamily: _fontFamily,
    );
    return base.copyWith(
      scaffoldBackgroundColor: _backgroundDark,
      appBarTheme: base.appBarTheme.copyWith(
        backgroundColor: _backgroundDark,
        foregroundColor: Colors.white,
        elevation: 0,
        titleTextStyle: base.textTheme.titleLarge?.copyWith(
          fontWeight: FontWeight.w700,
          color: Colors.white,
        ),
        iconTheme: const IconThemeData(color: Colors.white),
        centerTitle: false,
      ),
      textTheme: base.textTheme.apply(
        bodyColor: Colors.white,
        displayColor: Colors.white,
      ),
      chipTheme: base.chipTheme.copyWith(
        shape: const StadiumBorder(),
        backgroundColor: Colors.white.withOpacity(0.12),
        selectedColor: _secondaryColor,
        labelStyle: base.textTheme.bodyMedium?.copyWith(
          color: Colors.white,
          fontWeight: FontWeight.w600,
        ),
      ),
      drawerTheme: base.drawerTheme.copyWith(
        backgroundColor: _backgroundDark,
      ),
    );
  }
}
