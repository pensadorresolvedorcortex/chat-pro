enum NotificationActionOption {
  foreground('foreground'),
  destructive('destructive'),
  authenticationRequired('authenticationRequired');

  const NotificationActionOption(this.value);

  final String value;
}

class NotificationTextInputConfiguration {
  const NotificationTextInputConfiguration({
    required this.buttonTitle,
    this.placeholder,
  });

  final String buttonTitle;
  final String? placeholder;

  Map<String, dynamic> toMap() {
    return {
      'buttonTitle': buttonTitle,
      if (placeholder != null) 'placeholder': placeholder,
    };
  }
}

class NotificationAction {
  const NotificationAction({
    required this.identifier,
    required this.title,
    this.options = const <NotificationActionOption>{},
    this.textInput,
  });

  final String identifier;
  final String title;
  final Set<NotificationActionOption> options;
  final NotificationTextInputConfiguration? textInput;

  Map<String, dynamic> toMap() {
    return {
      'identifier': identifier,
      'title': title,
      if (options.isNotEmpty)
        'options': options.map((option) => option.value).toList(growable: false),
      if (textInput != null) 'textInput': textInput!.toMap(),
    };
  }
}

enum NotificationCategoryOption {
  customDismissAction('customDismissAction'),
  allowInCarPlay('allowInCarPlay'),
  hiddenPreviewsShowTitle('hiddenPreviewsShowTitle'),
  hiddenPreviewsShowSubtitle('hiddenPreviewsShowSubtitle'),
  allowAnnouncement('allowAnnouncement');

  const NotificationCategoryOption(this.value);

  final String value;
}

class NotificationCategory {
  const NotificationCategory({
    required this.identifier,
    this.actions = const <NotificationAction>[],
    this.options = const <NotificationCategoryOption>{},
    this.intentIdentifiers = const <String>[],
    this.hiddenPreviewsBodyPlaceholder,
    this.categorySummaryFormat,
  });

  final String identifier;
  final List<NotificationAction> actions;
  final Set<NotificationCategoryOption> options;
  final List<String> intentIdentifiers;
  final String? hiddenPreviewsBodyPlaceholder;
  final String? categorySummaryFormat;

  Map<String, dynamic> toMap() {
    return {
      'identifier': identifier,
      if (actions.isNotEmpty)
        'actions': actions.map((action) => action.toMap()).toList(growable: false),
      if (options.isNotEmpty)
        'options': options.map((option) => option.value).toList(growable: false),
      if (intentIdentifiers.isNotEmpty) 'intentIdentifiers': intentIdentifiers,
      if (hiddenPreviewsBodyPlaceholder != null)
        'hiddenPreviewsBodyPlaceholder': hiddenPreviewsBodyPlaceholder,
      if (categorySummaryFormat != null)
        'categorySummaryFormat': categorySummaryFormat,
    };
  }
}
