import 'package:flutter/material.dart';

class AnalysisDetailScreen extends StatelessWidget {
  const AnalysisDetailScreen({super.key, required this.analysisId});
  final String analysisId;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Analysis Detail')),
      body: Center(child: Text('Analysis: $analysisId')),
    );
  }
}
