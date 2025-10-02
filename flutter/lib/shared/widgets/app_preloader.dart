import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

class AppPreloader extends StatelessWidget {
  const AppPreloader({super.key, this.message});

  final String? message;

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;
    return Column(
      mainAxisSize: MainAxisSize.min,
      mainAxisAlignment: MainAxisAlignment.center,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        SvgPicture.asset(
          'assets/images/logo_inicio.svg',
          height: 96,
        ),
        const SizedBox(height: 24),
        CircularProgressIndicator(
          valueColor: AlwaysStoppedAnimation<Color>(colorScheme.secondary),
        ),
        if (message != null) ...[
          const SizedBox(height: 16),
          Text(
            message!,
            style: textTheme.titleMedium,
            textAlign: TextAlign.center,
          ),
        ],
      ],
    );
  }
}
