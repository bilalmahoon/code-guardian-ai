import 'package:flutter/material.dart';

abstract class AppColors {
  static const primary     = Color(0xFF6366F1); // Indigo
  static const secondary   = Color(0xFF8B5CF6); // Purple
  static const success     = Color(0xFF10B981); // Emerald
  static const warning     = Color(0xFFF59E0B); // Amber
  static const error       = Color(0xFFEF4444); // Red
  static const info        = Color(0xFF3B82F6); // Blue

  static const critical    = Color(0xFFDC2626);
  static const high        = Color(0xFFEA580C);
  static const medium      = Color(0xFFCA8A04);
  static const low         = Color(0xFF2563EB);

  // Dark theme surfaces
  static const darkBg      = Color(0xFF0F0F10);
  static const darkSurface = Color(0xFF1A1A1E);
  static const darkCard    = Color(0xFF222228);
  static const darkBorder  = Color(0xFF2E2E38);
}

abstract class AppTheme {
  static ThemeData light() {
    const colorScheme = ColorScheme.light(
      primary:   AppColors.primary,
      secondary: AppColors.secondary,
      error:     AppColors.error,
      surface:   Color(0xFFF8F9FA),
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      fontFamily: 'Inter',
      appBarTheme: const AppBarTheme(
        elevation: 0,
        scrolledUnderElevation: 1,
        centerTitle: false,
      ),
      cardTheme: CardTheme(
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: const BorderSide(color: Color(0xFFE5E7EB)),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(0, 48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
    );
  }

  static ThemeData dark() {
    const colorScheme = ColorScheme.dark(
      primary:   AppColors.primary,
      secondary: AppColors.secondary,
      error:     AppColors.error,
      surface:   AppColors.darkSurface,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: AppColors.darkBg,
      fontFamily: 'Inter',
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.darkSurface,
        elevation: 0,
        scrolledUnderElevation: 1,
        centerTitle: false,
      ),
      cardTheme: CardTheme(
        color: AppColors.darkCard,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: const BorderSide(color: AppColors.darkBorder),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(0, 48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: AppColors.darkBorder),
        ),
        filled: true,
        fillColor: AppColors.darkCard,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
    );
  }
}
