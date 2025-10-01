import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  static ThemeData get light {
    final base = ThemeData.light(useMaterial3: true);

    return base.copyWith(
      colorScheme: base.colorScheme.copyWith(
        primary: const Color(0xFF6645F6),
        secondary: const Color(0xFF1DD3C4),
        tertiary: const Color(0xFFE5BE49),
        error: const Color(0xFFDF5354),
        surface: Colors.white,
        surfaceVariant: const Color(0xFFF4F6FB),
        outline: const Color(0xFFCBD2E0),
      ),
      primaryColor: const Color(0xFF6645F6),
      scaffoldBackgroundColor: Colors.white,
      appBarTheme: AppBarTheme(
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF0C3C64),
        elevation: 0,
        centerTitle: false,
        titleTextStyle: GoogleFonts.inter(
          fontWeight: FontWeight.w600,
          fontSize: 20,
          color: const Color(0xFF0C3C64),
        ),
      ),
      textTheme: GoogleFonts.interTextTheme(base.textTheme).apply(
        bodyColor: const Color(0xFF0C3C64),
        displayColor: const Color(0xFF0C3C64),
      ),
      inputDecorationTheme: const InputDecorationTheme(
        border: OutlineInputBorder(),
        filled: true,
        fillColor: Color(0xFFF7F8FC),
      ),
      chipTheme: base.chipTheme.copyWith(
        backgroundColor: const Color(0xFFF0F2FA),
        selectedColor: const Color(0xFF6645F6),
        labelStyle: GoogleFonts.inter(
          fontWeight: FontWeight.w500,
          color: const Color(0xFF0C3C64),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFF6645F6),
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      useMaterial3: true,
    );
  }

  static ThemeData get dark {
    final base = ThemeData.dark(useMaterial3: true);

    return base.copyWith(
      colorScheme: base.colorScheme.copyWith(
        primary: const Color(0xFF6645F6),
        secondary: const Color(0xFF1DD3C4),
        tertiary: const Color(0xFFE5BE49),
        error: const Color(0xFFDF5354),
        surface: const Color(0xFF0C3C64),
        surfaceVariant: const Color(0xFF0F496D),
      ),
      scaffoldBackgroundColor: const Color(0xFF0C3C64),
      appBarTheme: AppBarTheme(
        backgroundColor: const Color(0xFF0C3C64),
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      textTheme: GoogleFonts.interTextTheme(base.textTheme).apply(
        bodyColor: Colors.white,
        displayColor: Colors.white,
      ),
      cardColor: const Color(0xFF14507A),
      useMaterial3: true,
    );
  }
}
