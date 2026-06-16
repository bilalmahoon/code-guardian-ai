import 'package:freezed_annotation/freezed_annotation.dart';

part 'analysis_model.freezed.dart';
part 'analysis_model.g.dart';

@freezed
class AnalysisModel with _$AnalysisModel {
  const factory AnalysisModel({
    required String id,
    required String organizationId,
    required String projectId,
    String? triggeredBy,
    required String triggerType,
    required String sourceType,
    String? sourceRef,
    required String status,
    int? overallScore,
    int? securityScore,
    int? performanceScore,
    int? architectureScore,
    int? maintainabilityScore,
    int? qualityScore,
    int? debtScore,
    int? devopsScore,
    String? aiProviderUsed,
    @Default(0) int tokensConsumed,
    int? processingMs,
    String? errorMessage,
    DateTime? startedAt,
    DateTime? completedAt,
    required DateTime createdAt,
  }) = _AnalysisModel;

  factory AnalysisModel.fromJson(Map<String, dynamic> json) =>
      _$AnalysisModelFromJson(json);
}

@freezed
class IssueModel with _$IssueModel {
  const factory IssueModel({
    required String id,
    required String analysisId,
    required String agentType,
    required String category,
    required String title,
    required String description,
    required String severity,
    String? filePath,
    int? lineStart,
    int? lineEnd,
    String? codeSnippet,
    String? ruleId,
    @Default(false) bool isFalsePositive,
    required DateTime createdAt,
  }) = _IssueModel;

  factory IssueModel.fromJson(Map<String, dynamic> json) =>
      _$IssueModelFromJson(json);
}

@freezed
class RecommendationModel with _$RecommendationModel {
  const factory RecommendationModel({
    required String id,
    required String issueId,
    required String analysisId,
    required String problemDescription,
    String? rootCause,
    String? riskLevel,
    required String recommendedFix,
    String? codeBefore,
    String? codeAfter,
    String? estimatedImprovement,
    required String status,
    required DateTime createdAt,
  }) = _RecommendationModel;

  factory RecommendationModel.fromJson(Map<String, dynamic> json) =>
      _$RecommendationModelFromJson(json);
}
