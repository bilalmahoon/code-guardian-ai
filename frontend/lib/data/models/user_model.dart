import 'package:freezed_annotation/freezed_annotation.dart';

part 'user_model.freezed.dart';
part 'user_model.g.dart';

@freezed
class UserModel with _$UserModel {
  const factory UserModel({
    required String id,
    required String name,
    required String email,
    String? avatarUrl,
    DateTime? emailVerifiedAt,
    DateTime? lastLoginAt,
    @Default(false) bool isOauthOnly,
    @Default([]) List<String> roles,
    @Default([]) List<OrganizationSummary> organizations,
    required DateTime createdAt,
  }) = _UserModel;

  factory UserModel.fromJson(Map<String, dynamic> json) =>
      _$UserModelFromJson(json);
}

@freezed
class OrganizationSummary with _$OrganizationSummary {
  const factory OrganizationSummary({
    required String id,
    required String name,
    required String slug,
    required String plan,
    required String role,
  }) = _OrganizationSummary;

  factory OrganizationSummary.fromJson(Map<String, dynamic> json) =>
      _$OrganizationSummaryFromJson(json);
}
